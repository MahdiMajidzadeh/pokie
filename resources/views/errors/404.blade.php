<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Table not found — pTable</title>
    <meta name="robots" content="noindex, nofollow">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-zinc-50 font-sans text-zinc-900 antialiased">
    <flux:main container class="max-w-xl">
        <div class="flex min-h-dvh flex-col">
            <div class="pt-6">
                <x-brand />
            </div>
            <div class="flex flex-1 items-center justify-center py-10">
                <mds:empty-state
                    icon="frown"
                    title="This table doesn't exist"
                    description="The link may be mistyped, or the table was never created. Starting a fresh one takes about ten seconds."
                >
                    <flux:button variant="primary" :href="route('home')" wire:navigate>Create a table</flux:button>
                </mds:empty-state>
            </div>
        </div>
    </flux:main>
    @fluxScripts
</body>
</html>
