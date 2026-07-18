<?php

namespace App\Http\Controllers;

use App\Jobs\IndexDocumentJob;
use App\Models\Document;
use App\Services\RagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class RagController extends Controller
{
    /**
     * INGEST DOCUMENT
     */
    public function ingest(Request $request): JsonResponse
    {
        /** @var array{content?: string|null} $payload */
        $payload = $request->validate([
            'content' => 'nullable|string|required_without:document',
            'document' => 'nullable|file|max:20480',
        ]);

        /** @var UploadedFile|null $file */
        $file = $request->files->get('document');
        $content = $payload['content'] ?? null;

        $safeName = null;
        $path = null;

        /**
         * STEP 1: File handling
         */
        if ($file !== null) {
            $originalName = $file->getClientOriginalName();

            $safeName = (string) Str::uuid().'_'.$originalName;

            $path = $file->storeAs('documents', $safeName, 'local');

            $content = null;
        }

        if ($file === null && ($content === null || $content === '')) {
            return response()->json([
                'message' => 'Please provide a file or content.',
            ], 422);
        }

        /**
         * STEP 2: Create document
         */
        /** @var Document $document */
        $document = Document::query()->create([
            'filename' => $file !== null && $safeName !== null
                ? $safeName
                : 'manual_'.(string) Str::uuid(),

            'original_filename' => $file?->getClientOriginalName(),

            'disk' => 'local',
            'path' => $path,
            'mime_type' => $file?->getMimeType(),
            'size' => $file?->getSize() ?? 0,
            'status' => 'uploaded',
        ]);

        /**
         * STEP 3: Queue indexing
         */
        IndexDocumentJob::dispatch($document->id);

        return response()->json([
            'status' => 'queued',
            'document_id' => $document->id,
        ]);
    }

    /**
     * QUERY RAG
     */
    public function query(Request $request, RagService $ragService): JsonResponse
    {
        /** @var array{query: string, session_id?: string|null, top_k?: int} $validated */
        $validated = $request->validate([
            'query' => 'required|string',
            'session_id' => 'nullable|string',
            'top_k' => 'nullable|integer|min:1|max:20',
        ]);

        $queryText = $validated['query'];
        $sessionId = $validated['session_id'] ?? null;
        $topK = $validated['top_k'] ?? 5;

        return response()->json(
            $ragService->answer($queryText, $sessionId, $topK)
        );
    }
}
