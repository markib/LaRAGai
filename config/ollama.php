<?php

return [
    'host' => env('OLLAMA_HOST', 'http://127.0.0.1:11434'),
    'model' => env('OLLAMA_MODEL', 'gemma2:2b'),
    'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text:latest'),
];
