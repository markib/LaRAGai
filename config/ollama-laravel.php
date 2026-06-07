<?php

return [
    'model' => env('OLLAMA_MODEL', 'gemma2:2b'),
    'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text:latest'),
    'url' => env('OLLAMA_URL', env('OLLAMA_HOST', 'http://127.0.0.1:11434')),
    'default_prompt' => env('OLLAMA_DEFAULT_PROMPT', 'Hello, how can I assist you today?'),
    'connection' => [
        'timeout' => env('OLLAMA_CONNECTION_TIMEOUT', 300),
    ],
];
