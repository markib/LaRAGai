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

    'retrieval' => [
        'top_k' => env('RAG_RETRIEVAL_TOP_K', 5),
        'window' => env('RAG_RETRIEVAL_WINDOW', 3),
    ],

    'prompt' => [
        'system' => env('RAG_PROMPT_SYSTEM', 'You are a retrieval-augmented assistant. Use the provided context to answer the user clearly and accurately.'),
    ],
];
