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
            <div class="flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-sm text-zinc-500 shadow-sm">
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 0 0-15 7.5 7.5 0 0 0 0 15Z"/></svg>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search chats..."
                    class="w-full border-0 bg-transparent outline-none placeholder:text-zinc-400"
                />
                @if ($search)
                    <button wire:click="$set('search', '')" class="shrink-0 rounded-full p-0.5 text-zinc-400 hover:text-zinc-600">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-4 flex-1 space-y-4 overflow-y-auto pr-1">
        @if ($conversations)
            @foreach ($conversations as $date => $dateConversations)
                <div>
                    <h3 class="mb-2 px-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-zinc-400">{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</h3>
                    <div class="space-y-2">
                        @foreach ($dateConversations as $conv)
                            @php
                                $preview = collect($conv['messages'] ?? [])->first(fn ($m) => $m['role'] === 'user')['message'] ?? null;
                            @endphp
                            <div class="group flex items-center gap-2 rounded-2xl border {{ $currentSessionId === $conv['session_id'] ? 'border-indigo-200 bg-indigo-50/80' : 'border-transparent bg-white/70 hover:border-zinc-200 hover:bg-zinc-50' }} p-2 shadow-sm transition">
                                <button
                                    class="flex-1 truncate rounded-xl px-2 py-2 text-left text-sm text-zinc-700"
                                    wire:click="selectConversation('{{ $conv['session_id'] }}')"
                                    title="{{ $preview ?? 'New conversation' }}">
                                    {{ Str::limit($preview ?? 'New conversation', 42) }}
                                </button>
                                <button
                                    class="rounded-xl p-2 text-zinc-400 opacity-0 transition group-hover:opacity-100 hover:bg-red-50 hover:text-red-500"
                                    x-on:click="
                                        document.getElementById('delete-modal-{{ $loop->index }}').showModal()
                                    "
                                    title="Delete">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                </button>

                                <dialog
                                    id="delete-modal-{{ $loop->index }}"
                                    class="rounded-3xl border border-zinc-200/70 bg-white p-0 shadow-2xl backdrop:bg-black/40 backdrop:backdrop-blur-sm open:flex"
                                    style="max-width: 400px; width: 90vw;"
                                >
                                    <div class="flex flex-col items-center px-8 pb-6 pt-8 text-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-red-50">
                                            <svg class="h-7 w-7 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                                        </div>
                                        <h3 class="mt-4 text-lg font-semibold text-zinc-900">Delete conversation</h3>
                                        <p class="mt-2 text-sm leading-6 text-zinc-500">
                                            This will permanently delete
                                            <span class="font-medium text-zinc-700">"{{ Str::limit($preview ?? 'this conversation', 36) }}"</span>
                                            and all its messages. This action cannot be undone.
                                        </p>
                                        <div class="mt-6 flex w-full gap-3">
                                            <button
                                                class="flex-1 rounded-2xl border border-zinc-200 bg-white px-4 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50"
                                                x-on:click="document.getElementById('delete-modal-{{ $loop->index }}').close()"
                                            >
                                                Cancel
                                            </button>
                                            <button
                                                class="flex-1 rounded-2xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500"
                                                wire:click="deleteConversation('{{ $conv['session_id'] }}')"
                                                x-on:click="document.getElementById('delete-modal-{{ $loop->index }}').close()"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </dialog>
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
