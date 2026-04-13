<?php

namespace App\Repositories;

use App\Models\Conversation;

class ConversationRepository
{
    public function getMessages(string $sessionId): array
    {
        return Conversation::firstWhere('session_id', $sessionId)?->messages ?? [];
    }

    public function appendMessage(string $sessionId, string $role, string $message): void
    {
        $conversation = Conversation::firstOrCreate(
            ['session_id' => $sessionId],
            ['messages' => []]
        );

        $conversation->messages = array_merge($conversation->messages ?? [], [
            ['role' => $role, 'message' => $message, 'created_at' => now()->toIso8601String()],
        ]);

        $conversation->save();
    }
}
