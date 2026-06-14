<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $filename
 * @property string|null $original_filename
 * @property string      $disk
 * @property string      $path
 * @property string|null $mime_type
 * @property int         $size
 * @property string      $status
 * @property string|null $error_message
 * @property Carbon|null $indexed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, DocumentChunk> $chunks
 * @property-read int|null $chunks_count
 * @property-read Collection<int, DocumentEmbedding> $embeddings
 * @property-read int|null $embeddings_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereIndexedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereOriginalFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereUpdatedAt($value)
 * @mixin \Illuminate\Database\Eloquent\Model
 * @mixin IdeHelperDocument
 */
class Document extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentFactory> */
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
        'indexed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'indexed_at' => 'datetime',
    ];

    /**
     * @return HasMany<DocumentChunk, $this>
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(DocumentChunk::class);
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
