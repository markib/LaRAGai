<?php

use Illuminate\Support\Facades\Route;

// Chat Dashboard
Route::get('/', function () {
    return redirect()->route('chat.dashboard');
})->name('home');

Route::get('/chat', function () {
    return view('chat-dashboard');
})->name('chat.dashboard');
