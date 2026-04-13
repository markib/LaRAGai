<?php

namespace App\Http\Controllers;

use App\Repositories\QdrantVectorRepository;
use Illuminate\Http\Request;

class QdrantController extends Controller
{
    public function store(Request $request, QdrantVectorRepository $qdrant)
    {
        $payload = $request->validate([
            'document_id' => 'required|integer',
            'vector' => 'required|array',
            'vector.*' => 'numeric',
            'metadata' => 'nullable|array',
        ]);

        $saved = $qdrant->saveEmbedding(
            $payload['document_id'], 
            $payload['vector'], 
            $payload['metadata'] ?? []
        );

        return response()->json([
            'status' => 'saved',
            'data' => $saved,
        ]);
    }

    public function search(Request $request, QdrantVectorRepository $qdrant)
    {
        $payload = $request->validate([
            'vector' => 'required|array',
            'vector.*' => 'numeric',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $results = $qdrant->search(
            $payload['vector'], 
            $payload['limit'] ?? config('rag.retrieval.top_k', 5)
        );

        return response()->json(['results' => $results]);
    }

    public function show(int $documentId, QdrantVectorRepository $qdrant)
    {
        $point = $qdrant->getPoint($documentId);

        if ($point === null) {
            return response()->json(['message' => 'Point not found.'], 404);
        }

        return response()->json($point);
    }

    public function destroy(int $documentId, QdrantVectorRepository $qdrant)
    {
        $qdrant->deletePoint($documentId);

        return response()->json(['status' => 'deleted']);
    }

    public function clear(QdrantVectorRepository $qdrant)
    {
        $qdrant->clearCollection();

        return response()->json(['status' => 'collection_cleared']);
    }
}
