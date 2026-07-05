@php
use Illuminate\Support\Str;
@endphp
<div class="flex h-full w-full flex-col">
    <div class="rounded-3xl border border-zinc-200/70 bg-white/80 p-4 shadow-sm backdrop-blur">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-indigo-500">Workspace</p>
                <h2 class="text-lg font-semibold text-zinc-900">Conversations</h2>
            </div>
            <button class="rounded-2xl bg-indigo-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-indigo-500" wire:click="$dispatch('newConversation')">
                + New Chat
            </button>
        </div>

        <div class="mt-4 rounded-2xl border border-zinc-200/70 bg-zinc-50/70 p-2">
            <label class="flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-sm text-zinc-500 shadow-sm">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 0 0-15 7.5 7.5 0 0 0 0 15Z"/></svg>
                Search chats
            </label>
        </div>
    </div>

    <div class="mt-4 flex-1 space-y-4 overflow-y-auto pr-1">
        @if ($conversations)
            @foreach ($conversations as $date => $dateConversations)
                <div>
                    <h3 class="mb-2 px-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-zinc-400">{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</h3>
                    <div class="space-y-2">
                        @foreach ($dateConversations as $conv)
                            <div class="group flex items-center gap-2 rounded-2xl border {{ $currentSessionId === $conv['session_id'] ? 'border-indigo-200 bg-indigo-50/80' : 'border-transparent bg-white/70 hover:border-zinc-200 hover:bg-zinc-50' }} p-2 shadow-sm transition">
                                <button
                                    class="flex-1 truncate rounded-xl px-2 py-2 text-left text-sm text-zinc-700"
                                    wire:click="selectConversation('{{ $conv['session_id'] }}')"
                                    title="{{ collect($conv['messages'] ?? [])->first(fn ($m) => $m['role'] === 'user')['message'] ?? 'New conversation' }}">
                                    {{ Str::limit(collect($conv['messages'] ?? [])->first(fn ($m) => $m['role'] === 'user')['message'] ?? 'New conversation', 42) }}
                                </button>
                                <button
                                    class="rounded-xl p-2 text-zinc-400 opacity-0 transition group-hover:opacity-100 hover:bg-red-50 hover:text-red-500"
                                    wire:click="deleteConversation('{{ $conv['session_id'] }}')"
                                    onclick="return confirm('Delete this conversation?')"
                                    title="Delete">
                                    ×
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @else
            <div class="rounded-2xl border border-dashed border-zinc-200 bg-white/70 p-6 text-center text-sm text-zinc-500">
                No conversations yet. Start a new chat.
            </div>
        @endif
    </div>
</div>