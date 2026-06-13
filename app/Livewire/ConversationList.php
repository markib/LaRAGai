<?php

namespace App\Livewire;

use App\Models\Conversation;
use Livewire\Attributes\On;
use Livewire\Component;

class ConversationList extends Component
{
    public array $conversations = [];

    public ?string $currentSessionId = null;

    public function mount(?string $currentSessionId = null)
    {
        $this->currentSessionId = $currentSessionId;
        $this->loadConversations();
    }

    #[On('conversationDeleted')]
    public function onConversationDeleted(string $sessionId)
    {
        $this->loadConversations();
    }

    public function loadConversations()
    {
        try {
            // Fetch all conversations, grouped by date
            $this->conversations = Conversation::orderBy('created_at', 'desc')
                ->get()
                ->groupBy(function ($item) {
                    return $item->created_at->format('Y-m-d');
                })
                ->toArray();
        } catch (\Exception $e) {
            $this->conversations = [];
        }
    }

    public function selectConversation(string $sessionId)
    {
        $this->currentSessionId = $sessionId;
        $this->dispatch('loadConversationRequested', sessionId: $sessionId);
    }

    public function deleteConversation(string $sessionId)
    {
        try {
            Conversation::where('session_id', $sessionId)->delete();
            $this->loadConversations();

            if ($this->currentSessionId === $sessionId) {
                $this->currentSessionId = null;
                $this->dispatch('conversationCleared');
            }
        } catch (\Exception $e) {
            // Handle error silently or dispatch error event
        }
    }

    public function render()
    {
        return view('livewire.conversation-list');
    }
}
