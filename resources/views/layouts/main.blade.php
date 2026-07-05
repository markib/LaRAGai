<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="A premium AI assistant workspace for RAG-powered conversations.">
    <title>LaRAGai | AI Assistant Workspace</title>

    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='75' font-size='75' fill='%23007bff'>🤖</text></svg>">
    @vite(['resources/css/app.css'])
    @livewireStyles
    @stack('styles')
</head>

<body class="h-full bg-transparent text-zinc-900 antialiased">
    <div id="app" class="min-h-full">
        @yield('content')
    </div>

    @livewireScripts
    @stack('scripts')
</body>

</html>