<div class="flex h-full flex-col" x-data="{}" x-init="
    const scrollToBottom = () => {
        requestAnimationFrame(() => {
            let c = document.querySelector('.chat-scroll');
            if (c) c.scrollTop = c.scrollHeight;
        });
    };

    document.addEventListener('livewire:navigated', scrollToBottom);
    document.addEventListener('livewire:load', scrollToBottom);
    setTimeout(scrollToBottom, 80);
">
    <header class="flex items-center justify-between border-b border-zinc-200/70 bg-white/70 px-4 py-3 backdrop-blur lg:px-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-indigo-500">AI Assistant Workspace</p>
            <h2 class="text-lg font-semibold text-zinc-900">RAG Chat</h2>
        </div>
        <div class="flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-sm text-zinc-600">
            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
            Gemma · Hybrid search · 1.2s
        </div>
    </header>

    <div class="flex-1 overflow-hidden">
        <div class="flex h-full flex-col lg:flex-row">
            <section class="flex min-h-0 flex-1 flex-col">
                <div class="chat-scroll flex-1 space-y-4 overflow-y-auto px-4 py-5 sm:px-6 lg:px-8">
                    @if ($messages)
                    @foreach ($messages as $message)
                    <div class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[84%] rounded-[24px] border px-4 py-3 shadow-sm {{ $message['role'] === 'user' ? 'border-indigo-200 bg-indigo-600 text-white' : 'border-zinc-200 bg-white text-zinc-700' }}">
                            <div class="mb-2 flex items-center gap-2 text-xs font-medium {{ $message['role'] === 'user' ? 'text-indigo-100' : 'text-zinc-500' }}">
                                <span>{{ ucfirst($message['role']) }}</span>
                                <span>•</span>
                                <span>{{ \Carbon\Carbon::parse($message['created_at'])->format('M d, H:i') }}</span>
                            </div>
                            <div class="text-sm leading-7 {{ $message['role'] === 'user' ? 'text-white/95' : 'text-zinc-700' }}">
                                {!! nl2br(e($message['message'])) !!}
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @else
                    <div class="flex h-full items-center justify-center">
                        <div class="max-w-xl rounded-[28px] border border-dashed border-zinc-300 bg-white/70 px-8 py-10 text-center shadow-sm">
                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-indigo-500">New conversation</p>
                            <h3 class="mt-3 text-2xl font-semibold text-zinc-900">Ask about your documents</h3>
                            <p class="mt-3 text-sm leading-7 text-zinc-600">The assistant will search embeddings and BM25, then answer with source-backed citations and retrieval insights.</p>
                        </div>
                    </div>
                    @endif

                    <div wire:loading.flex wire:target="submitQuery" class="flex justify-start">
                        <div class="max-w-[84%] rounded-[24px] border border-zinc-200 bg-white px-4 py-3 shadow-sm">
                            <div class="mb-2 flex items-center gap-2 text-xs font-medium text-zinc-500">
                                <span>Assistant</span>
                                <span>•</span>
                                <span>Streaming</span>
                            </div>
                            <div class="flex items-center gap-1 text-sm text-zinc-600">
                                <span class="inline-block h-2 w-2 animate-bounce rounded-full bg-indigo-400"></span>
                                <span class="inline-block h-2 w-2 animate-bounce rounded-full bg-indigo-400" style="animation-delay: 0.15s"></span>
                                <span class="inline-block h-2 w-2 animate-bounce rounded-full bg-indigo-400" style="animation-delay: 0.3s"></span>
                            </div>
                            <div wire:stream="answer" class="mt-2 text-sm leading-7 text-zinc-700"></div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-zinc-200/70 bg-white/80 px-4 py-4 backdrop-blur sm:px-6 lg:px-8">
                    <form wire:submit.prevent="submitQuery" class="rounded-[28px] border border-zinc-200 bg-zinc-50/80 p-3 shadow-inner">
                        <textarea
                            wire:model.live="currentQuery"
                            rows="3"
                            placeholder="Ask any question about your documents..."
                            class="w-full resize-none border-0 bg-transparent px-2 py-2 text-sm text-zinc-700 outline-none placeholder:text-zinc-400"
                            wire:keydown.enter.prevent="submitQuery"></textarea>
                        <div class="mt-2 flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200/80 pt-3">
                            <div class="flex items-center gap-2 text-sm text-zinc-500">
                                <span class="rounded-full bg-white px-3 py-1.5 shadow-sm">⌘K search</span>
                                <span class="rounded-full bg-white px-3 py-1.5 shadow-sm">Shift + Enter new line</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" class="rounded-2xl border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-600 transition hover:bg-zinc-100">Attach</button>
                                <button type="submit" class="rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500" wire:loading.attr="disabled" wire:target="submitQuery">
                                    {{ $currentQuery ? 'Send' : 'Ask' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </section>



            <aside class="w-full border-t border-zinc-200/70 bg-zinc-50/70 p-4 lg:w-[360px] lg:border-l lg:border-t-0">
                <div class="space-y-4">
                    <livewire:document-upload />
                    <div class="rounded-[24px] border border-zinc-200/70 bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-zinc-900">Retrieval context</h3>
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-600">Live</span>
                        </div>
                        <div class="mt-3 space-y-2" wire:loading.delay.shortest wire:target="submitQuery">
                            @php
                            $retrievalSteps = [
                            ['label' => 'Searching embeddings', 'percent' => '92%', 'tone' => 'from-indigo-500 to-sky-500'],
                            ['label' => 'Searching BM25', 'percent' => '78%', 'tone' => 'from-violet-500 to-indigo-500'],
                            ['label' => 'Hybrid ranking', 'percent' => '84%', 'tone' => 'from-emerald-500 to-green-500'],
                            ['label' => 'Generating answer', 'percent' => '68%', 'tone' => 'from-amber-500 to-orange-500'],
                            ];
                            @endphp
                            @foreach ($retrievalSteps as $index => $step)
                            <div class="rounded-2xl border border-zinc-200 bg-zinc-50/80 p-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2 text-sm font-medium text-zinc-700">
                                        <span class="h-2.5 w-2.5 rounded-full {{ $index === 0 ? 'animate-pulse bg-indigo-500' : 'bg-zinc-300' }}"></span>
                                        {{ $step['label'] }}
                                    </div>
                                    <span class="text-xs font-medium text-zinc-400">{{ $step['percent'] }}</span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-zinc-200">
                                    <div
                                        @class([ 'h-2 rounded-full bg-gradient-to-r transition-all duration-500' ,
                                        $step['tone'],
                                        ])
                                        @style([ 'width: ' . $step['percent'],
                                        ])>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="mt-3 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-3 text-sm text-emerald-700" wire:loading.remove wire:target="submitQuery">
                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                Hybrid confidence stays high when retrieved chunks are contextually aligned.
                            </div>
                        </div>
                    </div>

                    @if(!empty($retrievedDocuments))
                    <div class="rounded-[24px] border border-zinc-200/70 bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-zinc-900">Retrieved chunks</h3>
                            <span class="text-xs text-zinc-500">{{ count($retrievedDocuments) }} items</span>
                        </div>
                        <div class="mt-3 space-y-3">
                            @foreach($retrievedDocuments as $doc)
                            @php
                            $doc = (array) $doc; // ensure it's an array
                            @endphp
                            <div class="rounded-2xl border border-zinc-200 bg-zinc-50/70 p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-medium text-zinc-800">
                                        {{ $doc['originalFilename'] ?? $doc['original_filename'] ?? $doc['filename'] ?? 'Unknown' }}
                                    </p>
                                    <span class="text-xs text-zinc-500">
                                        Score {{ round($doc['score'] ?? 0, 3) }}
                                    </span>
                                </div>
                                <p class="mt-2 text-sm leading-6 text-zinc-600">
                                    {{ substr($doc['content'] ?? $doc['payload']['text'] ?? '', 0, 200) }}...
                                </p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if ($errorMessage)
                    <div class="rounded-[24px] border border-red-200 bg-red-50 p-3 text-sm text-red-600">
                        {{ $errorMessage }}
                    </div>
                    @endif
                </div>
            </aside>
        </div>
    </div>
</div>