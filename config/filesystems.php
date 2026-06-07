<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        // ✅ REQUIRED for Livewire uploads
        'livewire-tmp' => [
            'driver' => 'local',
            'root' => storage_path('app/private/livewire-tmp'),
            'throw' => false,
        ],

        // ✅ RAG production storage (IMPORTANT)
        'documents' => [
            'driver' => 'local',
            'root' => storage_path('app/documents'),
            'throw' => false,
        ],
    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
