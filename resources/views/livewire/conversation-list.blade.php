@php
use Illuminate\Support\Str;
@endphp
<div class="conversation-list-container">
    <div class="list-header">
        <h2>Conversations</h2>
        <button class="new-conversation-btn" wire:click="$dispatch('newConversation')">
            + New Chat
        </button>
    </div>

    <div class="conversations-wrapper">
        @if ($conversations)
        @foreach ($conversations as $date => $dateConversations)
        <div class="date-group">
            <h3 class="date-header">{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</h3>
            <div class="conversation-items">
                @foreach ($dateConversations as $conv)
                <div class="conversation-item {{ $currentSessionId === $conv['session_id'] ? 'active' : '' }}">
                    <button
                        class="conversation-button"
                        wire:click="selectConversation('{{ $conv['session_id'] }}')"
                        title="{{ collect($conv['messages'] ?? [])->first(fn ($m) => $m['role'] === 'user')['message'] ?? 'New conversation' }}">
                        <span class="conv-text">
                            {{ Str::limit(collect($conv['messages'] ?? [])->first(fn ($m) => $m['role'] === 'user')['message'] ?? 'New conversation', 40) }}
                        </span>
                    </button>
                    <button
                        class="delete-conversation-btn"
                        wire:click="deleteConversation('{{ $conv['session_id'] }}')"
                        onclick="return confirm('Delete this conversation?')"
                        title="Delete">
                        ✕
                    </button>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
        @else
        <div class="empty-conversations">
            <p>No conversations yet. Start a new chat!</p>
        </div>
        @endif
    </div>

    <style>
        .conversation-list-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            background-color: #f8f9fa;
            border-right: 1px solid #e0e0e0;
            width: 250px;
            overflow: hidden;
        }

        .list-header {
            padding: 16px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .list-header h2 {
            font-size: 1.1rem;
            margin: 0;
            color: #333;
        }

        .new-conversation-btn {
            padding: 6px 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 0.85rem;
            cursor: pointer;
            white-space: nowrap;
            transition: background-color 0.2s;
        }

        .new-conversation-btn:hover {
            background-color: #0056b3;
        }

        .conversations-wrapper {
            flex: 1;
            overflow-y: auto;
            padding: 8px 0;
        }

        .date-group {
            padding: 8px 0;
        }

        .date-header {
            font-size: 0.75rem;
            font-weight: 600;
            color: #999;
            text-transform: uppercase;
            padding: 8px 12px;
            margin: 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .conversation-items {
            display: flex;
            flex-direction: column;
        }

        .conversation-item {
            display: flex;
            gap: 4px;
            padding: 4px 8px;
            position: relative;
        }

        .conversation-item.active {
            background-color: #e7f3ff;
        }

        .conversation-button {
            flex: 1;
            text-align: left;
            padding: 8px 12px;
            background: none;
            border: 1px solid transparent;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: background-color 0.2s;
        }

        .conversation-button:hover {
            background-color: #f0f0f0;
        }

        .conversation-item.active .conversation-button {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }

        .conv-text {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .delete-conversation-btn {
            display: none;
            padding: 4px 8px;
            background-color: #ff6b6b;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: background-color 0.2s;
        }

        .conversation-item:hover .delete-conversation-btn {
            display: inline-block;
        }

        .delete-conversation-btn:hover {
            background-color: #c92a2a;
        }

        .empty-conversations {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
            text-align: center;
            padding: 20px;
            font-size: 0.9rem;
        }

        /* Scrollbar styling */
        .conversations-wrapper::-webkit-scrollbar {
            width: 6px;
        }

        .conversations-wrapper::-webkit-scrollbar-track {
            background-color: transparent;
        }

        .conversations-wrapper::-webkit-scrollbar-thumb {
            background-color: #d0d0d0;
            border-radius: 3px;
        }

        .conversations-wrapper::-webkit-scrollbar-thumb:hover {
            background-color: #999;
        }
    </style>
</div>