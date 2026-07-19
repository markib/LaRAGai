<?php

namespace App\Livewire;

use App\Models\Conversation;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class ConversationList extends Component
{
    /**
     * @var array<string, array<int, array{id: int, session_id: string, messages: array<array-key, mixed>|null, created_at: string, updated_at: string}>>
     */
    public array $conversations = [];

    public ?string $currentSessionId = null;

    public string $search = '';

    public function mount(?string $currentSessionId = null): void
    {
        $this->currentSessionId = $currentSessionId;
        $this->loadConversations();
    }

    #[On('conversationDeleted')]
    public function onConversationDeleted(string $sessionId): void
    {
        $this->loadConversations();
    }

    public function updatedSearch(): void
    {
        $this->loadConversations();
    }

    public function loadConversations(): void
    {
        try {
            $query = Conversation::query()
                ->orderBy('created_at', 'desc');

            if (trim($this->search) !== '') {
                $query->where(function ($q) {
                    $term = trim($this->search);
                    $q->where('session_id', 'like', "%{$term}%")
                        ->orWhere('messages', 'like', "%{$term}%");
                });
            }

            $collection = $query->get();

            /** @var Collection<int, Conversation> $collection */
            $this->conversations = $collection
                ->groupBy(function (Conversation $item) {
                    return $item->created_at ? $item->created_at->format('Y-m-d') : now()->format('Y-m-d');
                })
                ->toArray();
        } catch (\Exception $e) {
            $this->conversations = [];
        }
    }

    public function selectConversation(string $sessionId): void
    {
        $this->currentSessionId = $sessionId;
        $this->dispatch('loadConversationRequested', sessionId: $sessionId);
    }

    public function deleteConversation(string $sessionId): void
    {
        try {
            Conversation::query()->where('session_id', $sessionId)->delete();
            $this->loadConversations();

            if ($this->currentSessionId === $sessionId) {
                $this->currentSessionId = null;
                $this->dispatch('conversationCleared');
            }
        } catch (\Exception $e) {
            // Handle error silently or dispatch error event
        }
    }

    public function render(): View
    {
        return view('livewire.conversation-list');
    }
}
