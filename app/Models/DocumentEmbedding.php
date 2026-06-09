<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentEmbedding extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'chunk_id',
        'embedding',
        'model',
    ];

    protected $casts = [
        'embedding' => 'array',
    ];

    /*
    |-----------------------------------------
    | Relationships
    |-----------------------------------------
    */

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function chunk()
    {
        return $this->belongsTo(DocumentChunk::class, 'chunk_id');
    }
}
