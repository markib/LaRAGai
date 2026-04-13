<?php

return [
    'host' => env('OLLAMA_HOST', 'http://127.0.0.1:11434'),
    'model' => env('OLLAMA_MODEL', 'phi4-mini:latest'),
    'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text'),
];
