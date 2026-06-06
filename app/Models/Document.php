<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'original_filename',
        'disk',
        'path',
        'mime_type',
        'size',
        'status',
        'error_message',
        'source',
        'content',
        'metadata'];

    protected $casts = [
        'metadata' => 'array',
    ];
}
