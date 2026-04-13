<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['session_id', 'messages'];

    protected $casts = [
        'messages' => 'array',
    ];
}
