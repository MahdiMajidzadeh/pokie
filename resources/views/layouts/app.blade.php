<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Poker Table'))</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link href="https://cdn.jsdelivr.net/npm/@webpixels/css@3/dist/all.css" rel="stylesheet" crossorigin="anonymous">
    @endif
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous">
</head>
<body class="bg-light min-vh-100">
    <main class="container py-5" style="max-width: 720px;">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-0 border-0 mb-4 d-flex align-items-center justify-content-between" role="alert">
                <span>{{ session('success') }}</span>
                <button type="button" class="btn-close" onclick="this.closest('.alert').remove()" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-0 border-0 mb-4 d-flex align-items-center justify-content-between" role="alert">
                <span>{{ session('error') }}</span>
                <button type="button" class="btn-close" onclick="this.closest('.alert').remove()" aria-label="Close"></button>
            </div> 
        @endif
        @yield('content')
    </main>
    @if (!file_exists(public_path('build/manifest.json')) && !file_exists(public_path('hot')))
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    @endif
</body>
</html>
