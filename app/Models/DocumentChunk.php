<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int         $id
 * @property int         $document_id
 * @property int         $chunk_index
 * @property string      $content
 * @property int|null    $token_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Document $document
 * @property-read Collection<int, DocumentEmbedding> $embeddings
 * @property-read int|null $embeddings_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk whereChunkIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk whereDocumentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk whereTokenCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DocumentChunk whereUpdatedAt($value)
 * @mixin \Illuminate\Database\Eloquent\Model
 * @mixin IdeHelperDocumentChunk
 */
class DocumentChunk extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentChunkFactory> */
    use HasFactory;

    protected $fillable = [
        'document_id',
        'chunk_index',
        'content',
        'token_count',
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
     * @return HasMany<DocumentEmbedding, $this>
     */
    public function embeddings(): HasMany
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
        return Str::limit($this->content, $limit);
    }

    public function truncate(): void
    {
        $this->embeddings()->delete();
        $this->delete();
    }
}
