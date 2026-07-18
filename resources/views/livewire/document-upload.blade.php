<div class="space-y-4 rounded-3xl border border-zinc-200/70 bg-white/80 p-4 shadow-sm backdrop-blur">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-500">Knowledge</p>
            <h3 class="text-lg font-semibold text-zinc-900">Upload Documents</h3>
        </div>
        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-600">PDF / TXT / MD</span>
    </div>

    <form wire:submit="uploadDocument" class="space-y-3">
        <label for="file-input" class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 bg-zinc-50/80 px-4 py-6 text-center transition hover:border-indigo-400 hover:bg-indigo-50/50">
            <span class="text-2xl">📄</span>
            <span class="mt-2 text-sm font-medium text-zinc-700">
                @if (count($file) > 0)
                {{ count($file) }} file(s) selected
                @if (count($file) === 1)
                <span class="block text-xs text-zinc-500 mt-1 truncate max-w-[280px]">
                    {{ $file[0]->getClientOriginalName() }}
                </span>
                @endif
                @else
                Drag or click to upload multiple files
                @endif
            </span>
            <span class="mt-1 text-xs text-zinc-500">Supports TXT, PDF, DOCX and Markdown files</span>
        </label>
        <!-- Multiple file input -->
        <input
            type="file"
            wire:model="file"
            accept=".txt,.pdf,.doc,.docx,.md"
            class="hidden"
            id="file-input"
            multiple
            {{ $isUploading ? 'disabled' : '' }} />

        <button type="submit" class="w-full rounded-2xl bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-zinc-700" {{ $isUploading || count($file) === 0 ? 'disabled' : '' }}>
            @if ($isUploading)
            <span class="inline-flex items-center gap-2"><span class="h-3 w-3 animate-spin rounded-full border-2 border-white/30 border-t-white"></span> Processing {{ count($file) }} files(s)...</span>
            @else
            Upload {{ count($file) > 1 ? count($file) . ' files' : 'document' }}
            @endif
        </button>

        @if ($uploadStatus)
        <div class="rounded-2xl px-3 py-2 text-sm {{ str_contains($uploadStatus, 'Error') ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600' }}">
            {{ $uploadStatus }}
        </div>
        @endif

        @if ($isUploading || $uploadProgress > 0)
        <div class="rounded-2xl border border-zinc-200 bg-zinc-50/80 p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-zinc-400">Pipeline</p>
                    <p class="text-sm font-semibold text-zinc-800">{{ $uploadStage }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-zinc-600 shadow-sm">{{ $uploadProgress }}%</span>
                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-600 shadow-sm">{{ $elapsedSeconds }}s</span>
                </div>
            </div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-zinc-200">
                <div
                    @class(['h-2 rounded-full bg-gradient-to-r from-indigo-500 via-sky-500 to-emerald-500 transition-all duration-500'])
                    @style(["width: {{ $uploadProgress }}%"])></div>
            </div>
            <div class="mt-3 rounded-xl border border-zinc-200 bg-white px-3 py-2 text-xs text-zinc-500">
                <div class="flex items-center justify-between">
                    <span>Queue worker</span>
                    <span class="font-medium text-zinc-700">{{ $isUploading ? 'processing' : 'idle' }}</span>
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <span>Started</span>
                    <span class="font-medium text-zinc-700">{{ $startedAtLabel ?? '—' }}</span>
                </div>
                <div class="mt-1 flex items-center justify-between">
                    <span>Finished</span>
                    <span class="font-medium text-zinc-700">{{ $finishedAtLabel ?? '—' }}</span>
                </div>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2">
                @foreach ($uploadSteps as $step)
                <div class="flex items-center gap-2 rounded-xl border border-zinc-200 bg-white px-2.5 py-2 text-xs text-zinc-600">
                    <span class="h-2.5 w-2.5 rounded-full {{ $step['state'] === 'active' ? 'animate-pulse bg-indigo-500' : ($step['state'] === 'done' ? 'bg-emerald-500' : 'bg-zinc-300') }}"></span>
                    <span class="truncate">{{ $step['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </form>

    @if (count($uploadedDocuments) > 0)
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <h4 class="text-sm font-semibold text-zinc-700">Recent documents</h4>
            <span class="text-xs text-zinc-400">{{ count($uploadedDocuments) }} indexed</span>
        </div>
        @foreach ($uploadedDocuments as $doc)
        <div class="flex items-start gap-3 rounded-2xl border border-zinc-200/70 bg-zinc-50/70 p-3" wire:key="doc-{{ $doc['id'] }}">
            <span class="text-xl">📄</span>
            <div class="min-w-0">
                <p class="truncate text-sm font-medium text-zinc-800">{{ $doc['original_filename'] }}</p>
                <p class="text-xs text-zinc-500">{{ $doc['created_at'] }}</p>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>