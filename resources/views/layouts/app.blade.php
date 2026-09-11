<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <title>{{ $title ?? config('app.name', 'pTable') }}</title>

    {{-- requirement.md §4.2 / AR-19: belt-and-suspenders alongside the
         X-Robots-Tag header the NoIndex/EnsureAdminEnabled middleware send —
         a header can be stripped by an intermediary, a meta tag survives a
         page save. --}}
    <meta name="robots" content="noindex, nofollow">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-zinc-50 font-sans text-zinc-900 antialiased">
    {{-- Required for the Flux::toast() PHP facade to render anything. --}}
    <flux:toast position="top center" />

    <flux:main container class="max-w-xl">
        {{ $slot }}
    </flux:main>

    @fluxScripts
</body>
</html>
