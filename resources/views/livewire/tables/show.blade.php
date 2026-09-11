<div>
    {{-- AR-11: persistent banner while an admin session is elevating this view. --}}
    @if ($isAdminView)
        <flux:callout variant="warning" class="mb-4">
            <x-slot name="icon"><mds:icon icon="shield" class="size-5" /></x-slot>
            <flux:callout.heading>Viewing as super admin</flux:callout.heading>
            <flux:callout.text>You are not the host of this table. Actions you take here are logged.</flux:callout.text>
            <x-slot name="actions">
                <flux:button size="sm" wire:click="toggleAdminViewer">
                    {{ $canManage ? 'Exit admin view' : 'Back to admin view' }}
                </flux:button>
                @if ($canManage)
                    <flux:modal.trigger name="reveal-manager-link">
                        <flux:button size="sm" variant="ghost">Show manager link</flux:button>
                    </flux:modal.trigger>
                @endif
            </x-slot>
        </flux:callout>
    @endif

    <div class="mb-4">
        <flux:heading size="xl">{{ $table->name }}</flux:heading>
        <flux:badge size="sm" :color="$table->isOpen() ? 'lime' : 'zinc'">
            {{ $table->isOpen() ? 'Open' : 'Settled' }}
        </flux:badge>

        <div class="mt-3 flex flex-wrap gap-2">
            @if ($canManage)
                <x-copy-button :url="url('/'.$table->table_hash.'/'.$table->manager_hash)" label="Manager link" />
                <x-copy-button :url="url('/'.$table->table_hash)" label="Share link" />
            @else
                <x-copy-button :url="url('/'.$table->table_hash)" label="Share" />
            @endif
        </div>
    </div>

    <livewire:tables.board :table-id="$table->id" :can-manage="$canManage" :key="'board-'.$table->id" />

    {{-- FR-25/FR-26: the manager sees why they can't settle before they even try. --}}
    @if ($canManage && $table->isOpen())
        @php($ledger = $this->ledger())
        @unless ($ledger->isBalanced())
            <flux:callout variant="warning" class="mt-4">
                <x-slot name="icon"><mds:icon icon="alert-02" class="size-5" /></x-slot>
                <flux:callout.heading>Not ready to settle</flux:callout.heading>
                <flux:callout.text>
                    @if ($ledger->imbalance > 0)
                        You've cashed out {{ number_format($ledger->imbalance) }} more than was bought in — check the entries.
                    @else
                        {{ number_format(abs($ledger->imbalance)) }} is still on the table — cash out the remaining players.
                    @endif
                </flux:callout.text>
                @php($unclosed = $this->unclosedPlayers())
                @if ($unclosed->isNotEmpty())
                    <ul class="mt-2 list-disc space-y-0.5 pl-5 text-sm text-zinc-600">
                        @foreach ($unclosed as $player)
                            <li wire:key="unclosed-{{ $player->id }}">{{ $player->name }} hasn't cashed out yet</li>
                        @endforeach
                    </ul>
                @endif
            </flux:callout>
        @endunless
    @endif

    @error('rate')
        <flux:callout variant="danger" class="mt-4">
            <flux:callout.text>{{ $message }}</flux:callout.text>
        </flux:callout>
    @enderror

    @if ($canManage)
        <div class="h-24"></div>
        <div class="fixed inset-x-0 bottom-0 z-10 border-t border-zinc-200 bg-white/95 p-4 backdrop-blur">
            <div class="mx-auto flex max-w-xl gap-3">
                @if ($table->isOpen())
                    <flux:button wire:click="openAddPlayer" class="flex-1">
                        <x-slot name="icon"><mds:icon icon="add-01" class="size-4" /></x-slot>
                        Add player
                    </flux:button>
                    <flux:modal.trigger name="settle">
                        <flux:button variant="primary" class="flex-1" :disabled="! $this->canSettle()">
                            Settle table
                        </flux:button>
                    </flux:modal.trigger>
                @else
                    <flux:modal.trigger name="reopen">
                        <flux:button variant="danger" class="flex-1">Reopen table</flux:button>
                    </flux:modal.trigger>
                @endif
            </div>
        </div>
    @endif

    {{-- ===================== modals — always rendered, stable shape ===================== --}}

    <flux:modal name="player" class="md:w-96">
        <form wire:submit="savePlayer" class="space-y-4">
            <flux:heading size="lg">{{ $playerForm->editing_id ? 'Rename player' : 'Add player' }}</flux:heading>

            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="playerForm.name" autofocus />
                <flux:error name="playerForm.name" />
            </flux:field>

            @if (! $playerForm->editing_id && $table->default_buy_in)
                <flux:checkbox
                    wire:model="playerForm.with_buy_in"
                    :label="'Record a '.number_format($table->default_buy_in).' buy-in now'"
                />
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="entry" class="md:w-96">
        <form wire:submit="saveEntry" class="space-y-4">
            <flux:heading size="lg">
                {{ $entryForm->type === 'buy_in' ? 'Buy-in' : 'Cash out' }}
            </flux:heading>

            <flux:field>
                <flux:label>Player</flux:label>
                <flux:select wire:model="entryForm.player_id">
                    @foreach ($this->players() as $player)
                        <flux:select.option value="{{ $player->id }}">{{ $player->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="entryForm.player_id" />
            </flux:field>

            <flux:field>
                <flux:label>Amount</flux:label>
                <flux:input type="number" min="1" wire:model="entryForm.amount" />
                <flux:error name="entryForm.amount" />
            </flux:field>

            <flux:field>
                <flux:label>Note <span class="font-normal text-zinc-400">(optional)</span></flux:label>
                <flux:input wire:model="entryForm.note" maxlength="120" />
                <flux:error name="entryForm.note" />
            </flux:field>

            @if ($entryForm->type === 'cash_out')
                <flux:checkbox wire:model="entryForm.mark_left" label="Mark this player as left" />
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="settle" class="md:w-96">
        <div class="space-y-4">
            <flux:heading size="lg">Settle this table?</flux:heading>
            <flux:text>Entries and players will be locked. You can reopen later, but any paid-marks will be lost.</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button wire:click="settle" variant="primary">Settle</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="reopen" class="md:w-96">
        <div class="space-y-4">
            <flux:heading size="lg">Reopen this table?</flux:heading>
            <flux:text>The payment list will be cleared, including any paid-marks. Entries unlock for editing again.</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button wire:click="reopen" variant="danger">Reopen</flux:button>
            </div>
        </div>
    </flux:modal>

    @if ($isAdminView)
        <flux:modal name="reveal-manager-link" class="md:w-96">
            <div class="space-y-4">
                <flux:heading size="lg">Manager link</flux:heading>
                <flux:text>Share this only with the table's host (AR-13).</flux:text>
                <div class="flex items-center gap-2">
                    <code class="flex-1 truncate rounded-lg bg-zinc-50 px-3 py-2 text-xs">
                        {{ url("/{$table->table_hash}/{$table->manager_hash}") }}
                    </code>
                    <x-copy-button :url="url('/'.$table->table_hash.'/'.$table->manager_hash)" />
                </div>
                <flux:modal.close><flux:button class="w-full">Close</flux:button></flux:modal.close>
            </div>
        </flux:modal>
    @endif
</div>
