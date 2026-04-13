<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VectorRecord extends Model
{
    protected $fillable = ['document_id', 'vector', 'metadata'];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function setVectorAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['vector'] = '[' . implode(',', array_map(fn ($item) => is_numeric($item) ? $item : floatval($item), $value)) . ']';

            return;
        }

        $this->attributes['vector'] = $value;
    }

    public function getVectorAttribute($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
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
