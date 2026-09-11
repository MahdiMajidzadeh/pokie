<div class="flex min-h-dvh flex-col justify-center gap-6 py-10">
    <div class="text-center">
        <x-brand class="text-2xl" />
        <flux:heading size="lg" class="mt-2">Admin sign in</flux:heading>
    </div>

    <flux:card>
        <form wire:submit="login" class="space-y-4">
            <flux:field>
                <flux:label>Username</flux:label>
                <flux:input wire:model="form.username" autofocus autocomplete="username" />
            </flux:field>

            <flux:field>
                <flux:label>Password</flux:label>
                <flux:input type="password" wire:model="form.password" autocomplete="current-password" viewable />
                {{-- AR-20: one generic error, always attached here regardless of which field was actually wrong. --}}
                <flux:error name="form.password" />
            </flux:field>

            <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled" wire:target="login">
                <span wire:loading.remove wire:target="login">Sign in</span>
                <span wire:loading wire:target="login">Signing in…</span>
            </flux:button>
        </form>
    </flux:card>
</div>
