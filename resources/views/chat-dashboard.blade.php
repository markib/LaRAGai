@extends('layouts.main')

@section('content')
<div class="min-h-screen bg-transparent p-3 sm:p-4 lg:p-6" x-data="{ darkMode: false, sidebarOpen: true }">
    <div class="mx-auto flex h-[calc(100vh-1.5rem)] max-w-7xl flex-col overflow-hidden rounded-[32px] border border-zinc-200/70 bg-white/80 shadow-[0_35px_120px_-45px_rgba(15,23,42,0.35)] backdrop-blur-2xl sm:h-[calc(100vh-2rem)] dark:border-zinc-800 dark:bg-zinc-950/80">
        <div class="flex min-h-0 flex-1 flex-col lg:flex-row">
            <aside class="hidden w-80 flex-shrink-0 border-r border-zinc-200/70 bg-zinc-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-900/70 lg:flex">
                <livewire:conversation-list />
            </aside>

            <main class="flex min-h-0 flex-1 flex-col">
                <livewire:chat />
            </main>
            
            
        </div>
    </div>
</div>
@endsection