<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'content',
        'metadata',
        'embedding',
    ];

    protected $casts = [
        'metadata' => 'array',
        'embedding' => 'array',
    ];
}
