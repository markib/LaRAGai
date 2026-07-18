<?php

namespace App\Repositories;

use App\Models\Conversation;
use App\Repositories\Contracts\ConversationRepositoryInterface;

class ConversationRepository implements ConversationRepositoryInterface
{
    /**
     * @return array<int, mixed>
     */
    public function getMessages(string $sessionId): array
    {
        $conversation = Conversation::query()->firstWhere('session_id', $sessionId);

        return $conversation ? $conversation->messages : [];
    }

    public function appendMessage(string $sessionId, string $role, string $message): void
    {
        $conversation = Conversation::query()->firstOrCreate(
            ['session_id' => $sessionId],
            ['messages' => []]
        );

        $conversation->messages = array_merge($conversation->messages ?? [], [
            ['role' => $role, 'message' => $message, 'created_at' => now()->toIso8601String()],
        ]);

        $conversation->save();
    }

    public function deleteConversation(string $sessionId): void
    {
        Conversation::query()->where('session_id', $sessionId)->delete();
    }
}
