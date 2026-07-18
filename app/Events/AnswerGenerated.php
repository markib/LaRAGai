<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnswerGenerated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param array<int, array<string, mixed>> $documents
     */
    public function __construct(
        public string $sessionId,
        public string $answer,
        public array $documents = [],
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("chat.{$this->sessionId}");
    }

    public function broadcastAs(): string
    {
        return 'answer.generated';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'answer' => $this->answer,
            'documents' => $this->documents,
            'session_id' => $this->sessionId,
        ];
    }
}
