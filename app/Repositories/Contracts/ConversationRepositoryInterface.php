<?php

namespace App\Repositories\Contracts;

interface ConversationRepositoryInterface
{
    /**
     * Get messages for the conversation.
     *
     * @return array<int, mixed>
     */
    public function getMessages(string $sessionId): array;

    public function appendMessage(
        string $sessionId,
        string $role,
        string $message
    ): void;

    public function deleteConversation(string $sessionId): void;
}
