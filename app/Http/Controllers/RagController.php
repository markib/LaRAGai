<?php

namespace App\Http\Controllers;

use App\Jobs\IndexDocumentJob;
use App\Models\Document;
use App\Services\RagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RagController extends Controller
{
    /**
     * INGEST DOCUMENT (CLEAN VERSION)
     */
    public function ingest(Request $request)
    {
        $payload = $request->validate([
            'content' => 'nullable|string|required_without:document',
            'document' => 'nullable|file|max:20480',
        ]);

        $file = $request->file('document');
        $content = $payload['content'] ?? null;

        /**
         * STEP 1: Handle file upload
         */
        if ($file) {

            $originalName = $file->getClientOriginalName();

            $safeName = Str::uuid().'_'.$originalName;

            $path = $file->storeAs('documents', $safeName, 'local');

            $content = null; // parser will handle later
        }

        if (! $file && empty($content)) {
            return response()->json([
                'message' => 'Please provide a file or content.',
            ], 422);
        }

        /**
         * STEP 2: Create Document record FIRST
         */
        $document = Document::create([
            'filename' => $file ? $safeName : 'manual_'.Str::uuid(),
            'original_filename' => $file ? $file->getClientOriginalName() : null,
            'disk' => 'local',
            'path' => $file ? $path : null,
            'mime_type' => $file?->getMimeType(),
            'size' => $file?->getSize() ?? 0,
            'status' => 'uploaded',
        ]);

        /**
         * STEP 3: Dispatch job (ONLY document_id)
         */
        IndexDocumentJob::dispatch($document->id);

        return response()->json([
            'status' => 'queued',
            'document_id' => $document->id,
        ]);
    }

    /**
     * QUERY RAG (UNCHANGED - GOOD)
     */
    public function query(Request $request, RagService $ragService)
    {
        $payload = Validator::make($request->all(), [
            'query' => 'required|string',
            'session_id' => 'nullable|string',
            'top_k' => 'nullable|integer|min:1|max:20',
        ])->validated();

        return response()->json(
            $ragService->answer(
                $payload['query'],
                $payload['session_id'] ?? null,
                $payload['top_k'] ?? 5
            )
        );
    }
}
