<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaRAGai - Chat</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='75' font-size='75' fill='%23007bff'>🤖</text></svg>">
    @vite(['resources/css/app.css'])
    <!-- Livewire Styles -->
    @livewireStyles

    <!-- Base CSS -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
    </style>

    @stack('styles')
</head>

<body>
    <div id="app">
        @yield('content')
    </div>

    <!-- Alpine.js for DOM reactivity -->


    <!-- Livewire Scripts -->
    @livewireScripts

    @stack('scripts')
</body>

</html>