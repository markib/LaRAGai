<?php

return [
    'provider' => env('RAG_PROVIDER', 'ollama'),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_API_BASE', 'https://api.openai.com/v1'),
        'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'generation_model' => env('OPENAI_GENERATION_MODEL', 'gpt-4o-mini'),
    ],

    'vector_store' => env('RAG_VECTOR_STORE', 'mysql'),

    'qdrant' => [
        'host' => env('QDRANT_HOST', 'http://127.0.0.1:6333'),
        'api_key' => env('QDRANT_API_KEY'),
        'collection' => env('QDRANT_COLLECTION', 'documents'),
        'vector_name' => env('QDRANT_VECTOR_NAME', 'embeddings'),
        'vector_dim' => env('QDRANT_VECTOR_DIM', 1536),
        'distance' => env('QDRANT_DISTANCE', 'Cosine'),
    ],

    'chroma' => [
        'host' => env('CHROMA_HOST', 'http://localhost:8000'),
        'api_key' => env('CHROMA_API_KEY', ''),
        'tenant' => env('CHROMA_TENANT', 'default'),
        'database' => env('CHROMA_DATABASE', 'default'),
        'collection' => env('CHROMA_COLLECTION', 'documents'),
    ],

    'retrieval' => [
        'top_k' => env('RAG_RETRIEVAL_TOP_K', 5),
        'min_score' => env('RAG_RETRIEVAL_MIN_SCORE', 0.0),
        're_rank' => env('RAG_RETRIEVAL_RE_RANK', true),
        'window' => env('RAG_RETRIEVAL_WINDOW', 3),
    ],

    'chunk_size' => env('RAG_CHUNK_SIZE', 512),
    'chunk_overlap' => env('RAG_CHUNK_OVERLAP', 256),

    'prompt' => [
        'system' => env('RAG_PROMPT_SYSTEM', 'You are a retrieval-augmented assistant. Use the provided context to answer the user clearly and accurately.'),
    ],
];
