<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'LaRAGai') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ file_exists(public_path('favicon.svg')) ? asset('favicon.svg') . '?v=' . filemtime(public_path('favicon.svg')) : '' }}">
    <link rel="icon" type="image/png" href="{{ file_exists(public_path('favicon.png')) ? asset('favicon.png') . '?v=' . filemtime(public_path('favicon.png')) : '' }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin />
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased font-sans">
    <div class="relative min-h-screen bg-gradient-to-br from-zinc-50 via-white to-indigo-50/40 text-zinc-600 dark:from-zinc-950 dark:via-zinc-900 dark:to-indigo-950/30 dark:text-zinc-400">
        @if (file_exists(public_path('images/background.svg')))
        <img id="background" class="pointer-events-none absolute inset-x-0 top-0 w-full select-none opacity-1 dark:opacity-20" src="{{ asset('images/background.svg') . '?v=' . filemtime(public_path('images/background.svg')) }}" />
        @endif
        <div class="relative flex min-h-screen flex-col items-center justify-center selection:bg-indigo-500 selection:text-white">
            <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">
                <header class="grid grid-cols-2 items-center gap-2 py-10 lg:grid-cols-3">
                    <div class="flex lg:justify-center lg:col-start-2">
                        <x-application-logo class="h-14 w-auto text-indigo-500 dark:text-indigo-400" />
                    </div>
                </header>

                <main class="mt-6">
                    <div class="text-center">
                        <h1 class="text-4xl font-bold text-zinc-900 dark:text-white sm:text-5xl lg:text-6xl"><span class="text-zinc-400 dark:text-zinc-500">Welcome to <span class="text-indigo-500">RAG workspace</span></h1>
                        <p class="mt-6 text-lg leading-8 text-zinc-500 dark:text-zinc-400">A premium AI assistant workspace for RAG-powered conversations.</p>
                    </div>

                    <div class="mt-16 flex justify-center gap-x-6">
                        <a href="{{ route('login') }}" class="rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Log in</a>
                        @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="rounded-xl border border-zinc-200 bg-white/70 px-6 py-2.5 text-sm font-semibold text-zinc-700 shadow-sm backdrop-blur transition hover:bg-white hover:text-zinc-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-zinc-700 dark:bg-zinc-800/70 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white">Register</a>
                        @endif
                    </div>
                </main>

                <footer class="py-16 text-center text-sm text-zinc-400 dark:text-zinc-500">
                    <p>&copy; {{ date('Y') }} <a href="" class="font-semibold text-zinc-600 hover:text-indigo-500 dark:text-zinc-400 dark:hover:text-indigo-400">Bikram Maharjan</a>. All rights reserved.</p>
                </footer>
            </div>
        </div>
    </div>
</body>

</html>