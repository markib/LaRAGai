<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property int         $document_id
 * @property int         $chunk_id
 * @property string|null $model
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read DocumentChunk $chunk
 * @property-read Document $document
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentEmbedding newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentEmbedding newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentEmbedding query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentEmbedding whereChunkId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentEmbedding whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentEmbedding whereDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentEmbedding whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentEmbedding whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentEmbedding whereUpdatedAt($value)
 * @mixin \Illuminate\Database\Eloquent\Model
 * @mixin IdeHelperDocumentEmbedding
 */
class DocumentEmbedding extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentEmbeddingFactory> */
    use HasFactory;

    protected $fillable = [
        'document_id',
        'chunk_id',
        'model',
    ];

    /*
    |-----------------------------------------
    | Relationships
    |-----------------------------------------
    */

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * @return BelongsTo<DocumentChunk, $this>
     */
    public function chunk(): BelongsTo
    {
        return $this->belongsTo(DocumentChunk::class, 'chunk_id');
    }
}
