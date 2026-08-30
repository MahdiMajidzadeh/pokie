@section('title', 'Superadmin — Pokie')

@section('inline-errors', '1')

<div class="flex min-h-dvh flex-col">
    <div class="flex items-center px-6 pt-5 sm:px-10 sm:pt-6">
        <a href="{{ route('home') }}" class="text-[15px] font-semibold tracking-tight text-zinc-900">Pokie</a>
    </div>
    <div class="flex flex-1 items-center justify-center px-6 py-6">
        @if(! $passwordConfigured)
            <div class="flex w-full max-w-sm flex-col items-center gap-2.5 text-center">
                <div class="text-[22px] font-semibold tracking-tight text-zinc-900">Superadmin isn't set up</div>
                <p class="text-sm leading-relaxed text-zinc-500 [text-wrap:pretty]">
                    This install has no superadmin password configured, so this area is off. Set
                    <span class="rounded-md bg-zinc-100 px-1.5 py-0.5 font-mono text-[13px] text-zinc-700">SUPERADMIN_PASSWORD</span>
                    to enable it.
                </p>
                <flux:link href="{{ route('home') }}" class="mt-1 text-sm">Back to Pokie</flux:link>
            </div>
        @else
            <form wire:submit="login" class="flex w-full max-w-[300px] flex-col gap-4">
                <div class="flex flex-col gap-1.5">
                    <div class="text-[22px] font-semibold tracking-tight text-zinc-900">Superadmin</div>
                    <p class="text-sm text-zinc-500">All tables, one list. Hosts don't need this.</p>
                </div>

                @if($error)
                    <flux:callout variant="danger" icon="exclamation-triangle" text="{{ $error }}" />
                @endif

                <flux:input wire:model="form.password" type="password" label="Password" autofocus />

                <flux:button type="submit" variant="primary">Sign in</flux:button>
                <flux:link href="{{ route('home') }}" class="text-center text-sm">Back to Pokie</flux:link>
            </form>
        @endif
    </div>
</div>
