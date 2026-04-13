<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QdrantController;
use App\Http\Controllers\RagController;

Route::prefix('rag')->group(function () {
    Route::post('ingest', [RagController::class, 'ingest']);
    Route::post('query', [RagController::class, 'query']);
});

Route::prefix('qdrant')->group(function () {
    Route::post('points', [QdrantController::class, 'store']);
    Route::post('points/search', [QdrantController::class, 'search']);
    Route::get('points/{documentId}', [QdrantController::class, 'show']);
    Route::delete('points/{documentId}', [QdrantController::class, 'destroy']);
    Route::post('points/clear', [QdrantController::class, 'clear']);
});
