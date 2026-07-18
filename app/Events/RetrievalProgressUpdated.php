<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RetrievalProgressUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public string $sessionId,
        public string $label,
        public int $percent,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("chat.{$this->sessionId}");
    }

    public function broadcastAs(): string
    {
        return 'retrieval.progress';
    }

    public function broadcastWith(): array
    {
        return [
            'label' => $this->label,
            'percent' => $this->percent,
        ];
    }
}
