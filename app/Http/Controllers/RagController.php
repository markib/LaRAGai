<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\IndexDocumentJob;
use App\Services\RagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class RagController extends Controller
{
    public function ingest(Request $request)
    {
        $payload = $request->validate([
            'source' => 'nullable|string',
            'content' => 'nullable|string|required_without:document',
            'document' => 'nullable|file',
            'metadata' => 'nullable',
        ]);

        $metadata = $payload['metadata'] ?? [];
        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);
            $metadata = is_array($decoded) ? $decoded : [];
        }

        $content = $payload['content'] ?? null;
        $document = $request->file('document');

        if ($document) {
            $content = File::get($document->getRealPath());
            if (empty($payload['source'])) {
                $payload['source'] = $document->getClientOriginalName();
            }
        }

        if (empty($content)) {
            return response()->json([
                'message' => 'Please provide document content or upload a file.',
            ], 422);
        }

        $source = $payload['source'] ?? 'manual-upload';

        IndexDocumentJob::dispatch($source, $content, $metadata);

        return response()->json(["status" => "queued", "source" => $source]);
    }

    public function query(Request $request, RagService $ragService)
    {
        $payload = Validator::make($request->all(), [
            'query' => 'required|string',
            'session_id' => 'nullable|string',
            'top_k' => 'nullable|integer|min:1|max:20',
        ])->validated();

        $result = $ragService->answer(
            $payload['query'],
            $payload['session_id'] ?? null,
            $payload['top_k'] ?? null
        );

        return response()->json($result);
    }
}
