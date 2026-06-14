<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property array<int, float> $vector
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VectorRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VectorRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VectorRecord query()
 * @mixin \Illuminate\Database\Eloquent\Model
 * @mixin IdeHelperVectorRecord
 */
class VectorRecord extends Model
{
    protected $fillable = ['document_id', 'vector', 'metadata'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Set the vector attribute.
     *
     * @param array<int, float|int>|string|mixed $value
     */
    public function setVectorAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['vector'] = '[' . implode(',', array_map(fn($item) => is_numeric($item) ? $item : floatval($item), $value)) . ']';

            return;
        }

        $this->attributes['vector'] = $value;
    }

    /**
     * Get the vector attribute.
     *
     * @param string|mixed $value
     * @return array<int, float>
     */
    public function getVectorAttribute($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return array_map('floatval', $decoded);
            }

            $trimmed = trim($value, '[]');
            if ($trimmed === '') {
                return [];
            }

            return array_map('floatval', explode(',', $trimmed));
        }

        return (array) $value;
    }
}
