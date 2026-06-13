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
        'indexed_at', ];

    protected $casts = [
        'indexed_at' => 'datetime',
    ];

    public function chunks()
    {
        return $this->hasMany(DocumentChunk::class);
    }

    public function embeddings()
    {
        return $this->hasMany(DocumentEmbedding::class);
    }

    /*
    |-----------------------------------------
    | Helpers
    |-----------------------------------------
    */

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsIndexed(): void
    {
        $this->update([
            'status' => 'indexed',
            'indexed_at' => now(),
        ]);
    }

    public function markAsFailed(string $message): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $message,
        ]);
    }
}
