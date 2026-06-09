<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'chunk_index',
        'content',
        'token_count',
    ];



    public function document()
    {
        return $this->belongsTo(Document::class);
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

    public function shortContent(int $limit = 120): string
    {
        return \Illuminate\Support\Str::limit($this->content, $limit);
    }
}
