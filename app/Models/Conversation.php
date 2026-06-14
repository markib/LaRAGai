<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int                          $id
 * @property string                       $session_id
 * @property array<array-key, mixed>|null $messages
 * @property Carbon|null                  $created_at
 * @property Carbon|null                  $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereMessages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Conversation whereUpdatedAt($value)
 *
 * @mixin Model
 * @mixin IdeHelperConversation
 */
class Conversation extends Model
{
    protected $fillable = ['session_id', 'messages'];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'messages' => 'array',
    ];
}
