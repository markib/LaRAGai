<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class ChatTokenStreamed implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public string $sessionId;

    public string $token;

    public function __construct(string $sessionId, string $token)
    {
        $this->sessionId = $sessionId;
        $this->token = $token;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.'.$this->sessionId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'token.streamed';
    }
}
