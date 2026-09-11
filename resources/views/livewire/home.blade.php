<div class="flex min-h-dvh flex-col justify-center gap-10 py-10">
    <div class="text-center">
        <x-brand class="text-2xl" />
        <flux:heading size="xl" class="mt-4">Settle up after poker night</flux:heading>
        <flux:text class="mx-auto mt-2 max-w-sm">
            Track buy-ins and cash-outs on your phone. At the end of the night, get an
            exact list of who pays whom — no arguments, no spreadsheet.
        </flux:text>
    </div>

    <flux:card class="space-y-5">
        <form wire:submit="create" class="space-y-5">
            <flux:field>
                <flux:label>Table name</flux:label>
                <flux:input wire:model="form.name" placeholder="Poker night — {{ now()->format('j M') }}" autofocus />
                <flux:error name="form.name" />
            </flux:field>

            <flux:field>
                <flux:label>Default buy-in <span class="font-normal text-zinc-400">(optional)</span></flux:label>
                <flux:input type="number" min="1" wire:model="form.default_buy_in" placeholder="e.g. 100" />
                <flux:description>Prefills every "+ Buy-in" so you don't retype it each time.</flux:description>
                <flux:error name="form.default_buy_in" />
            </flux:field>

            <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled" wire:target="create">
                <span wire:loading.remove wire:target="create">Create table</span>
                <span wire:loading wire:target="create">Creating…</span>
            </flux:button>
        </form>
    </flux:card>

    <div class="grid grid-cols-3 gap-4 text-center text-sm text-zinc-500">
        <div>
            <div class="mb-1 text-2xl">1</div>
            Create a table and add players as they sit down
        </div>
        <div>
            <div class="mb-1 text-2xl">2</div>
            Record buy-ins and cash-outs as the night goes
        </div>
        <div>
            <div class="mb-1 text-2xl">3</div>
            Settle — get the shortest list of who pays whom
        </div>
    </div>
</div>
