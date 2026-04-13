<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = ['source', 'content', 'metadata'];

    protected $casts = [
        'metadata' => 'array',
    ];
}
