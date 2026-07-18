# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

LaRAGai — a local-first RAG (Retrieval-Augmented Generation) stack built on Laravel 12, Livewire 3 (with Volt), Alpine.js, TailwindCSS, Ollama, and Qdrant. The frontend is a single-page chat workspace (`/chat`) with live progress streaming over Laravel Reverb. Documents (PDF/DOCX/TXT/MD) are uploaded, chunked, embedded via Ollama, stored in Qdrant, and retrieved through a hybrid vector + BM25 pipeline that re-ranks with reciprocal rank fusion before prompting Ollama to generate the final answer.

## Common commands

All commands assume Windows / Git Bash at the project root (`C:\xampp\htdocs\LaRAGai`).

- **Install**: `composer install` then `npm install`
- **Environment**: copy `.env.example` to `.env`, then `php artisan key:generate`
- **Migrate**: `php artisan migrate` (development DB lives at `database/database.sqlite`; the active `.env` may be PostgreSQL)
- **Run dev server (Laravel + Vite together)**: `composer dev` (runs `php artisan serve` + `npm run dev` concurrently via the `dev` script in `composer.json` — note: `composer.json` defines separate `dev`/`serve`/`build` scripts; use `php artisan serve` and `npm run dev` in two terminals if you want HMR)
- **Build frontend assets**: `npm run build`
- **Format / lint code style**: `./vendor/bin/pint` (Laravel preset; `pint.json` enforces alphabetical imports, no unused imports, vertical phpdoc alignment)
- **Static analysis**: `./vendor/bin/phpstan analyse` (Larastan, level 6 — see `phpstan.neon`; PHPStan relies on `_ide_helper.php` and `_ide_helper_models.php` being generated)
- **Run tests**: `php artisan test` (or `vendor/bin/pest`). The custom `php artisan test` command is registered as `TestCommand` and forces `APP_ENV=testing`, sqlite at `database/testing.sqlite`, `QUEUE_CONNECTION=sync`, `RAG_VECTOR_STORE=local`. To run a single test, append `--filter=<name>` (e.g. `php artisan test --filter=submitQuery`).
- **Reset RAG state** (drops documents/chunks and clears Qdrant): `php artisan rag:reset`
- **Bulk ingest from disk**: `php artisan rag:ingest <path> [--source=...]` (file or directory)
- **Reverb WebSocket server** (required for live progress): `php artisan reverb:start` (config in `config/reverb.php`; broadcasts on `0.0.0.0:8080` by default)

## Architecture overview

### Service boundaries

The RAG engine is composed of pluggable interfaces resolved by `app/Providers/RagServiceProvider.php`:

- `App\Services\Contracts\EmbeddingProviderInterface` — `embed(string): array<float>` (Ollama's `/v1/embeddings`)
- `App\Services\Contracts\GenerationProviderInterface` — `generate(prompt, context): string` (Ollama chat via `Cloudstudio\Ollama`)
- `App\Services\Contracts\RetrievalProviderInterface` — `search(query, limit, progressCallback, sessionId): array<RetrievalResult>`
- `App\Repositories\VectorRepositoryInterface` — `saveEmbedding`, `search`, `getPoint`, `deletePoint`, `clearCollection`; concrete impl is `QdrantVectorRepository`
- `App\Services\Retrieval\PostgresBm25Retriever` — keyword search using PostgreSQL `ts_rank` (requires `english` text search config; won't work on sqlite)

The active provider is selected by `RAG_PROVIDER` (default `ollama`) and `RAG_VECTOR_STORE` (default `mysql` in `config/rag.php`; the shipped `.env` sets it to `qdrant`). All provider wiring lives in `RagServiceProvider::register()`.

> Heads-up: the `VectorRepositoryInterface` binding in `RagServiceProvider` currently returns `null` when `rag.vector_store !== 'qdrant'` — so a `local` vector store target is not actually wired up. Tests sidestep this by binding `QdrantVectorRepository` directly via service resolution; do not assume a non-Qdrant store works without extending the provider.

### Document ingestion pipeline

1. **Entry points**:
   - HTTP: `POST /api/rag/ingest` (`App\Http\Controllers\RagController::ingest`) accepts a `document` file or `content` string, creates a `Document` row with `status='uploaded'`, and dispatches `App\Jobs\IndexDocumentJob`.
   - Livewire: `App\Livewire\DocumentUpload::uploadDocument` does the same flow for the in-app uploader.
   - Console: `php artisan rag:ingest <path>` dispatches `IndexDocumentJob` for files in a directory.

2. **Async indexer** (`App\Jobs\IndexDocumentJob`): parses the file via `App\Services\DocumentParser` (PDF via `smalot/pdfparser`, DOCX via `ZipArchive` on `word/document.xml`, TXT/MD with UTF-8 normalization + null-byte strip), then delegates to `App\Services\RagService::ingestDocument()`.

3. **`RagService::ingestDocument`**: marks the document `processing`, sentence-splits content (default 900-char chunks, 150-char overlap; see `RagService::chunkContent`), persists each chunk to `document_chunks`, calls the embedder, records metadata in `document_embeddings` (the actual vector lives in Qdrant, not the DB), upserts the point via `QdrantVectorRepository::saveEmbedding`, then marks the document `indexed` (or `failed` with the exception message). Status transitions live on `App\Models\Document` (`markAsProcessing`, `markAsIndexed`, `markAsFailed`).

### Retrieval + answer pipeline

1. User submits a query in `App\Livewire\Chat` (Livewire + Alpine). `Chat::submitQuery` validates, pushes the user message into local state, resets the four-step progress UI (`Searching embeddings` → `BM25 search` → `Hybrid ranking` → `Generating answer`), then dispatches `App\Jobs\ProcessRagQuery`.

2. `App\Jobs\ProcessRagQuery` runs through the queue (sync in tests, redis/db in dev). It:
   - Appends the user message to the `Conversation` row via `ConversationRepository`.
   - Calls `RagService::answer()` with a progress callback.
   - On success, appends the assistant message and broadcasts `App\Events\AnswerGenerated` on `chat.{sessionId}`.
   - On failure, broadcasts an error answer and logs the exception.

3. `RagService::answer()` delegates retrieval to `App\Services\Providers\LocalRetrievalProvider`, which:
   - Embeds the query with the embedder.
   - Calls `QdrantVectorRepository::search` for top `limit*4` vector hits.
   - Calls `PostgresBm25Retriever::search` for top `limit*4` keyword hits.
   - Merges the two ranked lists via **reciprocal rank fusion** (k=60) in `LocalRetrievalProvider::reciprocalRankFusion`, returning the top `limit` chunk IDs.
   - Hydrates matching `DocumentChunk` rows (with their parent `Document`) into `App\DTO\RetrievalResult` instances — `RetrievalResult` implements `Livewire\Wireable` for round-tripping through Livewire components.

4. The retrieved context is concatenated and passed to `RagService::buildPrompt`, which produces a system+context+question block. The provider (Ollama) generates the final answer.

5. Progress is streamed to the browser via two `ShouldBroadcastNow` events on the private channel `chat.{sessionId}`:
   - `App\Events\RetrievalProgressUpdated` (`retrieval.progress`)
   - `App\Events\AnswerGenerated` (`answer.generated`)

   The client subscribes in `resources/js/echo.js` (Laravel Echo + Reverb) and `resources/views/livewire/chat.blade.php` (Alpine `retrievalProgress` component maps the label set in `LocalRetrievalProvider::notifyProgress` to the four visible steps).

### Layered structure

- `app/Http/Controllers` — JSON API (`RagController`, `QdrantController`) and Breeze `Auth\VerifyEmailController`. Web routes are Breeze-style (welcome, dashboard, profile) and the chat UI lives at `/chat` via the Livewire view, mounted from `resources/views/chat-dashboard.blade.php`.
- `app/Livewire` — Volt-friendly components: `Chat`, `ConversationList`, `DocumentUpload`. Volt is mounted on `resources/views/livewire` and `resources/views/pages` (`VoltServiceProvider`).
- `app/Models` — `Document`, `DocumentChunk`, `DocumentEmbedding`, `Conversation`, `VectorRecord` (a no-longer-used legacy model whose set/get mutators encode/decode vectors as bracketed CSV), and `User` (Breeze).
- `app/Repositories` — `QdrantVectorRepository` (HTTP client via `Illuminate\Support\Facades\Http`, auto-creates the collection, handles dimension mismatches and Qdrant API quirks including legacy `vector` vs named `embeddings`), `DocumentRepository`, `ConversationRepository`.
- `app/Services` — `RagService` (orchestrator), `DocumentParser`, `Providers/*` (Ollama and LocalRetrieval concrete impls), `Retrieval/PostgresBm25Retriever`, `Contracts/*` (interfaces), `Support/Config` (typed env helpers).
- `app/DTO` — `RetrievalResult` (Livewire `Wireable`), `IngestResult`, `Bm25Result`, `VectorResult`.
- `app/Events`, `app/Jobs` — broadcast + queue layer (see above).
- `database/migrations` — `documents`, `document_chunks` (unique on `document_id, chunk_index`), `document_embeddings` (the `embedding` json column was dropped in `2026_06_13_192249_drop_embedding_from_document_embeddings` — vectors now live exclusively in Qdrant), `conversations` (json `messages`), Breeze `users` + sessions + password reset.

### Configuration surfaces

- `config/rag.php` — provider/model names, Qdrant host/key/collection/dim/distance, retrieval `top_k`/`min_score`/`re_rank`/`window`, `chunk_size`/`chunk_overlap`, system prompt.
- `config/ollama.php` and `config/ollama-laravel.php` — Ollama host, model, embedding model. `OllamaProvider` builds a candidate list (`nomic-embed-text:latest` and slash variants) and falls back across them on errors.
- `config/reverb.php` + `config/broadcasting.php` — Reverb server on `0.0.0.0:8080`, default broadcaster `reverb`.
- `config/livewire.php` — class namespace `App\Livewire`, layout `components.layouts.app`, view path `resources/views/livewire`.
- `phpunit.xml` / `.env.testing` — sqlite at `database/testing.sqlite`, `RAG_VECTOR_STORE=local`, sync queue, array session/cache, bcrypt rounds 4.
- `phpstan.neon` — Larastan level 6; ignores `Ollama::model` and `Route::name` static-method noise; scans the generated `_ide_helper*.php` files (regenerate with `php artisan ide-helper:generate` + `ide-helper:models` after schema changes — see CI).

## Testing notes

- Pest 3 with `RefreshDatabase` is bound for the `Feature` suite (`tests/Pest.php`).
- `tests/Helpers/OllamaMock.php` provides `mockOllamaEmbeddings()` (mocks `*v1/embeddings` HTTP) and `mockOllamaCompletion()` (mocks the `Cloudstudio\Ollama\Facades\Ollama` chain). The default mock vector is 768-dim; Qdrant dim is configured separately, so a mismatch will throw — `tests/Feature/PdfIngestionTest.php` exercises the full ingest path.
- `tests/Helpers/VectorAssertions.php` adds Pest expectations: `toBeVectorLength`, `toBeCosineSimilarityAtLeast`, `toBeGoldenSimilarTo` (uses `similar_text`).
- `tests/Feature/ChatSubmitQueryTest.php` covers `App\Livewire\Chat::submitQuery` (uses `Bus::fake()` for `ProcessRagQuery`).
- `App\Livewire\Chat::loadRetrievalResults()` currently returns a hardcoded fake `RetrievalResult` so the chat view renders even before the Reverb pipeline completes — the real retrieval is delivered asynchronously via `AnswerGenerated`. Don't change the public shape of this method without updating `chat-dashboard.blade.php` consumers.

## CI / DevSecOps

`.github/workflows/ci.yml` (PHP 8.4 + Node 20, on `main` push and PRs): composer install, npm ci, `composer validate`, `pint --test`, `php -l` syntax sweep, Qdrant via `docker run`, migrations on sqlite, `ide-helper:generate` + `ide-helper:models`, `phpstan analyse`, `php artisan test`, `composer audit`, `npm audit --audit-level=moderate`, `npm run build`, `npm test`, and `aquasecurity/trivy-action` filesystem scan (CRITICAL/HIGH). `docs/devsecops.md` captures the broader policy.

## File storage

Uploaded files land on the `local` disk at `storage/app/documents/` (see `config/filesystems.php` and `RagController::ingest`). Document rows store the path plus mime type; parsing happens during `IndexDocumentJob`. The `public` disk is also configured but unused by RAG.

## Things to know before changing things

- The retrieval provider broadcasts progress with these exact label strings (the Alpine `update()` function in `chat.blade.php` maps them): `Searching embeddings`, `Vector search`, `BM25 search`, `Hybrid ranking`, `Retrieval complete`, `No results found`, `Generating answer`. Keep the labels in sync if you touch `LocalRetrievalProvider::notifyProgress`.
- `PostgresBm25Retriever` issues raw `ts_rank`/`to_tsvector` queries — it does **not** work on the sqlite test DB. Tests don't exercise BM25 directly; only the vector path is asserted.
- The `Ollama` facades import is `Cloudstudio\Ollama\Facades\Ollama`; PHPStan is told to ignore the static `model()` call on it (`phpstan.neon`).
- `database/testing.sqlite` is a real on-disk file (not `:memory:`) — its contents persist between runs and are committed to the repo in some states. Use `php artisan test` (which sets the testing env) rather than running pest against the dev DB.
