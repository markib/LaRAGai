<p align="center">
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/badge/LaRAGai-1a1a2e?style=for-the-badge&logo=laravel&logoColor=white&label=⚡">
        <img src="https://img.shields.io/badge/LaRAGai-1a1a2e?style=for-the-badge&logo=laravel&logoColor=white&label=⚡" alt="LaRAGai">
    </picture>
</p>

<p align="center">
    <em>Local-first RAG stack — Laravel 12, Livewire 3, Ollama, Qdrant</em>
</p>

<p align="center">
    <a href="#overview">Overview</a> •
    <a href="#architecture">Architecture</a> •
    <a href="#quick-start">Quick Start</a> •
    <a href="#usage">Usage</a> •
    <a href="#testing">Testing</a> •
    <a href="#configuration">Configuration</a>
</p>

---

## Overview

LaRAGai is a **local-first Retrieval-Augmented Generation** application. Upload documents (PDF, DOCX, TXT, MD), chunk and embed them via Ollama, store vectors in Qdrant, and query everything through a hybrid vector + BM25 retrieval pipeline with reciprocal rank fusion — all running locally with no cloud dependency.

**Stack**

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12 (PHP 8.3+) |
| Frontend | Livewire 3 + Volt, Alpine.js, TailwindCSS |
| LLM | Ollama (local) |
| Vector Store | Qdrant (primary) |
| WebSocket | Laravel Reverb (live progress) |
| Search | Hybrid vector + PostgreSQL BM25 + RRF |
| Auth | Laravel Breeze |
| Testing | Pest 3 |
| CI | GitHub Actions (Pint, PHPStan, Trivy) |

---

## Architecture

```
User → Chat UI (Livewire/Alpine)
         │
         ▼
    ProcessRagQuery (queue job)
         │
    ┌────┴──────────────┐
    ▼                   ▼
EmbeddingProvider  RetrievalProvider
(Ollama)           (LocalRetrievalProvider)
                        │
              ┌─────────┼─────────┐
              ▼         ▼         ▼
        Vector       BM25      Hybrid RRF
        (Qdrant)   (Postgres)  (re-rank)
              │         │
              └─────────┘
                   │
                   ▼
           GenerationProvider
               (Ollama)
                   │
                   ▼
           Answer + Progress
           (Reverb → Alpine UI)
```

### Ingestion Pipeline

```
Upload (HTTP / Livewire / CLI)
    → IndexDocumentJob (queue)
    → DocumentParser (PDF/DOCX/TXT/MD)
    → RagService::ingestDocument
        → Sentence split (chunk + overlap)
        → Embed via Ollama
        → Upsert to Qdrant
    → Document status: uploaded → processing → indexed
```

### Retrieval + Answer Pipeline

```
User query → Chat::submitQuery
    → ProcessRagQuery (queue)
    → RagService::answer
        → Embed query
        → Vector search (Qdrant, top-k×4)
        → BM25 search (PostgreSQL ts_rank, top-k×4)
        → Reciprocal Rank Fusion (k=60)
        → Hydrate RetrievalResult DTOs
        → Build prompt (system + context + question)
        → Generate answer (Ollama)
    → Broadcast AnswerGenerated (Reverb)
    → Alpine UI renders progress steps
```

---

## Quick Start

### Prerequisites

- PHP 8.3+
- Composer
- Node.js 20+
- [Ollama](https://ollama.ai) running locally
- [Qdrant](https://qdrant.tech) running locally (optional — falls back to MySQL)

### Installation

```bash
# Clone & enter
git clone <repo> laragai && cd laragai

# Install dependencies
composer install
npm install

# Environment
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate

# Start services (three terminals)
ollama serve                                    # Terminal 1
php artisan reverb:start                        # Terminal 2 (WebSocket for progress)
php artisan serve & npm run dev                 # Terminal 3 (or: composer dev)
```

Visit `http://localhost:8000/chat` after registering.

> **Tip**: Pull a small model first: `ollama pull nomic-embed-text` (embeddings), `ollama pull llama2` (generation).

---

## Usage

### CLI — Bulk Ingest

```bash
# Ingest all files in a directory
php artisan rag:ingest sample_documents

# Ingest a single file
php artisan rag:ingest sample_documents/faq.txt

# Reset RAG state (drops chunks + clears Qdrant)
php artisan rag:reset
```

### API — Ingest & Query

```bash
# Ingest a document
curl -X POST http://localhost:8000/api/rag/ingest \
  -F "document=@report.pdf"

# Query
curl -X POST http://localhost:8000/api/rag/query \
  -H "Content-Type: application/json" \
  -d '{"query": "What is the main finding?"}'
```

### Qdrant API

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/qdrant/points` | Upsert point |
| POST | `/api/qdrant/points/search` | Vector search |
| GET | `/api/qdrant/points/{id}` | Get point |
| DELETE | `/api/qdrant/points/{id}` | Delete point |
| POST | `/api/qdrant/points/clear` | Clear collection |

---

## Project Structure

```
app/
├── Console/Commands/      # Artisan commands (ingest, reset, test)
├── DTO/                   # Data transfer objects (RetrievalResult, etc.)
├── Events/                # Broadcast events (AnswerGenerated, progress)
├── Http/Controllers/      # API controllers (RagController, QdrantController)
├── Jobs/                  # Queue jobs (IndexDocumentJob, ProcessRagQuery)
├── Livewire/              # Livewire components (Chat, DocumentUpload, etc.)
├── Models/                # Eloquent models (Document, Conversation, etc.)
├── Providers/             # Service providers (RagServiceProvider)
├── Repositories/          # Persistence layer (Qdrant, Document, Conversation)
├── Services/              # Core logic
│   ├── Contracts/         # Provider interfaces
│   ├── Providers/         # Ollama, LocalRetrieval implementations
│   ├── Retrieval/         # PostgresBM25 retriever
│   ├── DocumentParser.php # File parsing (PDF/DOCX/TXT/MD)
│   └── RagService.php     # Main orchestrator
└── Support/               # Helpers (Config)
config/                    # Laravel + RAG config files
database/migrations/       # Schema migrations
resources/
├── js/                    # Alpine + Laravel Echo
├── css/                   # TailwindCSS
└── views/                 # Blade templates (Livewire components)
routes/
├── web.php                # Chat, dashboard, welcome
├── api.php                # RAG + Qdrant API
└── channels.php           # Reverb broadcasting channels
tests/
├── Feature/               # Feature tests (Pest)
├── Unit/                  # Unit tests
└── Helpers/               # OllamaMock, VectorAssertions
```

---

## Configuration

Key environment variables (`.env`):

| Variable | Default | Description |
|----------|---------|-------------|
| `RAG_PROVIDER` | `ollama` | Generation provider |
| `OLLAMA_HOST` | `http://127.0.0.1:11434` | Ollama server |
| `OLLAMA_MODEL` | `llama2` | Generation model |
| `RAG_VECTOR_STORE` | `mysql` | Vector store (`qdrant` or `mysql`) |
| `QDRANT_HOST` | `http://127.0.0.1:6333` | Qdrant API |
| `QDRANT_API_KEY` | — | Qdrant auth key |
| `QDRANT_COLLECTION` | `documents` | Qdrant collection name |
| `QDRANT_VECTOR_DIM` | `1536` | Embedding dimension |
| `RAG_CHUNK_SIZE` | `512` | Document chunk size (chars) |
| `RAG_CHUNK_OVERLAP` | `256` | Chunk overlap (chars) |
| `RAG_RETRIEVAL_TOP_K` | `5` | Results to return |
| `RAG_RETRIEVAL_RE_RANK` | `true` | Enable RRF re-ranking |

See `config/rag.php` for all options.

---

## Testing

```bash
# Run all tests
php artisan test

# Run a specific test
php artisan test --filter=submitQuery

# Static analysis
./vendor/bin/phpstan analyse

# Code style
./vendor/bin/pint
```

Tests use `database/testing.sqlite` with `QUEUE_CONNECTION=sync` and `RAG_VECTOR_STORE=local`. The `tests/Helpers/OllamaMock.php` provides HTTP mocks for Ollama endpoints.

---

## Events & Live Progress

The chat UI streams progress through Laravel Reverb:

1. `RetrievalProgressUpdated` — `chat.{sessionId}` (step labels: `Searching embeddings`, `Vector search`, `BM25 search`, `Hybrid ranking`, `Retrieval complete`, `Generating answer`)
2. `AnswerGenerated` — `chat.{sessionId}` (final answer payload)

The Alpine component in `resources/views/livewire/chat.blade.php` maps these labels to a 4-step progress UI.

---

## CI

GitHub Actions runs on every push/PR to `main`:

- `pint --test` — code style
- `php -l` — syntax check
- PHPStan — static analysis (level 6)
- Pest — full test suite (with Qdrant container)
- `composer audit` + `npm audit` — vulnerability scan
- Trivy — filesystem security scan (CRITICAL/HIGH)
- `npm run build` — frontend asset compilation

---

## License

[MIT](LICENSE)
