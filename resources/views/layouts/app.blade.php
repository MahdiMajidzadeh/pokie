<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Poker Table'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link href="https://cdn.jsdelivr.net/npm/@webpixels/css@3/dist/all.css" rel="stylesheet" crossorigin="anonymous">
    @endif
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous">
</head>
<body class="min-vh-100" style="background-color: #fafafa; font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;">
    <main class="container py-8 px-4" style="max-width: 720px;">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 mb-4 d-flex align-items-center justify-content-between shadow-sm" style="background-color: #f0fdf4; color: #166534;" role="alert">
                <span><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}</span>
                <button type="button" class="btn-close" onclick="this.closest('.alert').remove()" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 mb-4 d-flex align-items-center justify-content-between shadow-sm" style="background-color: #fef2f2; color: #991b1b;" role="alert">
                <span><i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}</span>
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
