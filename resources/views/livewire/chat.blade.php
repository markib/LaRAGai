<div class="flex h-full flex-col"
    x-data="{
         sessionId: '{{ $sessionId }}',
         isProcessing: @entangle('isProcessing'),
         errorMessage: @entangle('errorMessage')
     }"
    x-init="
        const scrollToBottom = () => {
            requestAnimationFrame(() => {
                let c = document.querySelector('.chat-scroll');
                if (c) c.scrollTop = c.scrollHeight;
            });
        };

        document.addEventListener('livewire:navigated', scrollToBottom);
        document.addEventListener('livewire:load', scrollToBottom);
        Livewire.hook('message.processed', () => setTimeout(scrollToBottom, 50));
        setTimeout(scrollToBottom, 80);
     ">

    <header class="flex items-center justify-between border-b border-zinc-200/70 bg-white/70 px-4 py-3 backdrop-blur lg:px-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-indigo-500">AI Assistant Workspace</p>
            <h2 class="text-lg font-semibold text-zinc-900">RAG Chat</h2>
        </div>
        <div class="flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-sm text-zinc-600">
            <span class="h-2.5 w-2.5 rounded-full" :class="isProcessing ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500'"></span>
            Gemma · Hybrid search
        </div>
    </header>

    <div class="flex-1 overflow-hidden">
        <div class="flex h-full flex-col lg:flex-row">
            <section class="flex min-h-0 flex-1 flex-col">
                <div class="chat-scroll flex-1 space-y-4 overflow-y-auto px-4 py-5 sm:px-6 lg:px-8">
                    @if ($messages)
                    @foreach ($messages as $index => $message)
                    <div wire:key="message-{{ $message['id'] ?? $index }}" class="flex {{ $message['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
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

                    <!-- ⚡ Native Livewire Chat stream target -->
                    <!-- This remains completely hidden (via CSS empty:hidden) until stream begins -->
                    <div wire:loading wire:target="submitQuery" class="flex justify-start">
                        <div class="max-w-[84%] rounded-[24px] border border-zinc-200 bg-white px-4 py-3 shadow-sm text-zinc-700">
                            <div class="mb-2 flex items-center gap-2 text-xs font-medium text-zinc-500">
                                <span>Assistant</span>
                                <span>•</span>
                                <span class="animate-pulse text-indigo-500 font-semibold">Streaming...</span>
                            </div>
                            <!-- Livewire will stream directly into this inner div text node -->
                            <div wire:stream="answer" class="text-sm leading-7 text-zinc-700 whitespace-pre-line"></div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-zinc-200/70 bg-white/80 px-4 py-4 backdrop-blur sm:px-6 lg:px-8">
                    <form wire:submit="submitQuery" class="rounded-[28px] border border-zinc-200 bg-zinc-50/80 p-3 shadow-inner">
                        <textarea
                            id="currentQuery"
                            wire:model="currentQuery"
                            rows="3"
                            placeholder="Ask any question about your documents..."
                            class="w-full resize-none border-0 bg-transparent px-2 py-2 text-sm text-zinc-700 outline-none placeholder:text-zinc-400"
                            x-on:keydown.enter.prevent="if (!$event.shiftKey) { $el.form.requestSubmit(); }"></textarea>

                        <div class="mt-2 flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200/80 pt-3">
                            <div class="flex items-center gap-2 text-sm text-zinc-500">
                                <span class="rounded-full bg-white px-3 py-1.5 shadow-sm">Enter to Send</span>
                                <span class="rounded-full bg-white px-3 py-1.5 shadow-sm">Shift + Enter new line</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" class="rounded-2xl border border-zinc-200 bg-white px-3 py-2 text-sm font-medium text-zinc-600 transition hover:bg-zinc-100">Attach</button>

                                <button type="submit" class="rounded-2xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500"
                                    wire:loading.attr="disabled"
                                    wire:target="submitQuery"
                                    wire:loading.class="opacity-60">
                                    Send
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </section>

            <aside class="w-full border-t border-zinc-200/70 bg-zinc-50/70 p-4 lg:w-[360px] lg:border-l lg:border-t-0 overflow-y-auto">
                <div class="space-y-4">
                    <livewire:document-upload wire:key="document-upload" />

                    <!-- 📡 Laravel Reverb strictly handles progress bars -->
                    <div class="rounded-[24px] border border-zinc-200/70 bg-white p-4 shadow-sm"
                        x-data="{ 
                             steps: [
                                 { label: 'Searching embeddings', percent: 0, tone: 'from-indigo-500 to-sky-500', active: false, completed: false },
                                 { label: 'Searching BM25', percent: 0, tone: 'from-violet-500 to-indigo-500', active: false, completed: false },
                                 { label: 'Hybrid ranking', percent: 0, tone: 'from-emerald-500 to-green-500', active: false, completed: false },
                                 { label: 'Generating answer', percent: 0, tone: 'from-amber-500 to-orange-500', active: false, completed: false }
                             ],
                             init() {
                                 if (window.Echo && sessionId) {
                                     window.Echo.private(`chat.${sessionId}`)
                                         .listen('.retrieval.progress', (e) => {
                                             this.updateProgress(e.label, e.percent);
                                             scrollToBottom();
                                         });
                                 }
                             },
                             updateProgress(label, percent) {
                                 const stages = {
                                     'Searching embeddings': 0, 'Vector search': 0,
                                     'Searching BM25': 1, 'BM25 search': 1,
                                     'Hybrid ranking': 2,
                                     'Generating answer': 3, 'Retrieval complete': 3
                                 };
                                 const current = stages[label];
                                 if (current === undefined) return;

                                 this.steps.forEach((step, index) => {
                                     if (index < current) {
                                         step.percent = 100;
                                         step.completed = true;
                                         step.active = false;
                                     } else if (index === current) {
                                         step.percent = percent;
                                         step.active = percent < 100;
                                         step.completed = percent >= 100;
                                     } else {
                                         step.percent = 0;
                                         step.active = false;
                                         step.completed = false;
                                     }
                                 });
                             }
                         }">

                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-zinc-900">Retrieval context</h3>
                            <span :class="isProcessing ? 'bg-amber-50 text-amber-600 animate-pulse' : 'bg-zinc-100 text-zinc-500'"
                                class="rounded-full px-2.5 py-1 text-xs font-medium transition-colors duration-300">
                                <span x-text="isProcessing ? 'Live Processing' : 'Idle'"></span>
                            </span>
                        </div>

                        <div class="mt-3 space-y-2">
                            <template x-for="(step, index) in steps" :key="index">
                                <div class="rounded-2xl border border-zinc-200 bg-zinc-50/80 p-3 transition-all duration-300">
                                    <div class="flex justify-between text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 rounded-full shadow-sm transition-all duration-300"
                                                :class="{
                                                    'animate-pulse bg-indigo-500': step.active,
                                                    'bg-emerald-500': step.completed,
                                                    'bg-zinc-300': !step.active && !step.completed,
                                                }">
                                            </span>
                                            <span class="text-zinc-700 font-medium tracking-tight" x-text="step.label"></span>
                                        </div>
                                        <span class="text-zinc-500 font-mono text-xs font-semibold" x-text="`${step.percent}%`"></span>
                                    </div>

                                    <div class="mt-2.5 h-1.5 rounded-full bg-zinc-200 overflow-hidden shadow-inner">
                                        <div class="h-full rounded-full bg-gradient-to-r transition-all duration-500 ease-out"
                                            :class="step.tone"
                                            :style="`width: ${step.percent}%`">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    @if(!empty($retrievedDocuments))
                    <div class="rounded-[24px] border border-zinc-200/70 bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-zinc-900">Retrieved chunks</h3>
                            <span class="text-xs text-zinc-500">{{ count($retrievedDocuments) }} items</span>
                        </div>
                        <div class="mt-3 space-y-3">
                            @foreach($retrievedDocuments as $index => $doc)
                            @php $doc = (array) $doc; @endphp
                            <div wire:key="doc-{{ $index }}" class="rounded-2xl border border-zinc-200 bg-zinc-50/70 p-3">
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