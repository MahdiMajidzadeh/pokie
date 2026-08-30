<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Pokie'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-white font-sans text-zinc-900 antialiased">
    <flux:toast />
    @if (session('success'))
        <div x-data x-init="$flux.toast({ text: @js(session('success')), variant: 'success' })"></div>
    @endif
    @if (session('error') && ! trim($__env->yieldContent('inline-errors')))
        <div x-data x-init="$flux.toast({ text: @js(session('error')), variant: 'danger' })"></div>
    @endif
    @yield('content')
    @yield('scripts')
    @fluxScripts
</body>
</html>
