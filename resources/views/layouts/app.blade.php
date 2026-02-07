<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Poker Table'))</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            background: '#ffffff', foreground: '#222222', card: '#ffffff', primary: '#222222',
                            muted: '#f5f5f5', accent: '#0099ff', border: '#eeeeee', destructive: '#d0011b',
                            'muted-foreground': '#999999', 'primary-foreground': '#ffffff', 'accent-foreground': '#ffffff'
                        }
                    }
                }
            }
        </script>
    @endif
</head>
<body class="bg-background min-h-screen text-foreground">
    <main class="container mx-auto px-4 py-8 max-w-2xl rounded">
        @if (session('success'))
            <div class="mb-4 p-3 bg-muted border border-border text-foreground rounded">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-3 bg-destructive/10 border border-destructive text-destructive rounded">
                {{ session('error') }}
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
