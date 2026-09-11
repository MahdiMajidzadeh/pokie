@php
    $table = $this->table();
    $ledger = $this->ledger();
    $players = $this->players();
@endphp
<div @if ($table->isOpen()) wire:poll.5s @endif>
    {{-- FR-21: table totals --}}
    <div class="mb-4 grid grid-cols-4 gap-2 rounded-xl border border-zinc-200 bg-white p-4 text-center">
        <div>
            <div class="text-xs text-zinc-400">In</div>
            <x-money :amount="$ledger->potIn" size="sm" />
        </div>
        <div>
            <div class="text-xs text-zinc-400">Out</div>
            <x-money :amount="$ledger->potOut" size="sm" />
        </div>
        <div>
            <div class="text-xs text-zinc-400">On table</div>
            <x-money :amount="$ledger->onTable" size="sm" />
        </div>
        <div>
            <div class="text-xs text-zinc-400">Players</div>
            <div class="font-semibold tabular-nums">{{ $players->count() }}</div>
        </div>
    </div>

    @if ($table->isSettled())
        {{-- S5: final results, winners first (FR-28) --}}
        <flux:card class="mb-4">
            <flux:heading size="lg" class="mb-3">Final results</flux:heading>
            <div class="space-y-2">
                @foreach ($players->sortByDesc(fn ($player) => $ledger->forPlayer($player->id)['net']) as $player)
                    <div class="flex items-center justify-between" wire:key="result-{{ $player->id }}">
                        <span>{{ $player->name }}</span>
                        <x-money :amount="$ledger->forPlayer($player->id)['net']" signed />
                    </div>
                @endforeach
            </div>
        </flux:card>

        {{-- FR-29..FR-32: payment list, grouped by payer, with paid toggles --}}
        @php
            $payments = $this->payments();
            $paid = $this->paidCount();
            $total = $payments->count();
        @endphp
        <flux:card class="mb-4">
            <div class="mb-3 flex items-center justify-between">
                <flux:heading size="lg">Payments</flux:heading>
                <flux:text size="sm">{{ $paid }} of {{ $total }} settled</flux:text>
            </div>

            @if ($total > 0)
                <flux:progress :value="$paid" :max="$total" class="mb-3" />
            @endif

            <div class="space-y-2">
                @forelse ($payments as $payment)
                    <div class="flex items-center justify-between gap-2 rounded-lg border border-zinc-100 p-3" wire:key="payment-{{ $payment->id }}">
                        <div class="text-sm">
                            <span class="font-medium">{{ $payment->fromPlayer->name }}</span>
                            pays
                            <span class="font-medium">{{ $payment->toPlayer->name }}</span>
                            <x-money :amount="$payment->amount" size="sm" class="ms-1" />
                        </div>

                        @if ($canManage)
                            <flux:switch
                                wire:click="$dispatch('toggle-paid', { paymentId: {{ $payment->id }} })"
                                :checked="$payment->paid_at !== null"
                            />
                        @else
                            <flux:badge size="sm" :color="$payment->paid_at ? 'lime' : 'zinc'">
                                {{ $payment->paid_at ? 'Paid' : 'Unpaid' }}
                            </flux:badge>
                        @endif
                    </div>
                @empty
                    <flux:text size="sm" class="text-zinc-400">No payments needed — everyone broke even.</flux:text>
                @endforelse
            </div>
        </flux:card>
    @else
        {{-- S3/S4: live player list --}}
        <div class="space-y-2">
            @forelse ($players as $player)
                @php($totals = $ledger->forPlayer($player->id))
                <div class="rounded-lg border border-zinc-200 bg-white p-3" wire:key="player-{{ $player->id }}">
                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="truncate font-medium">{{ $player->name }}</span>
                                @if ($player->has_left)
                                    <flux:badge size="sm" color="zinc">Left</flux:badge>
                                @endif
                            </div>
                            <div class="text-xs text-zinc-400">
                                in {{ number_format($totals['total_in']) }} · out {{ number_format($totals['total_out']) }}
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <x-money :amount="$totals['net']" signed size="sm" />

                            @if ($canManage)
                                <flux:dropdown>
                                    <flux:button size="xs" variant="ghost" square aria-label="More actions for {{ $player->name }}">
                                        <x-slot name="icon"><mds:icon icon="more-vertical" class="size-4" /></x-slot>
                                    </flux:button>
                                    <flux:menu>
                                        <flux:menu.item wire:click="$dispatch('rename-player', { playerId: {{ $player->id }} })">
                                            Rename
                                        </flux:menu.item>
                                        <flux:menu.item wire:click="$dispatch('toggle-left', { playerId: {{ $player->id }} })">
                                            {{ $player->has_left ? 'Mark active' : 'Mark left' }}
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item
                                            variant="danger"
                                            wire:click="$dispatch('remove-player', { playerId: {{ $player->id }} })"
                                            wire:confirm="Remove {{ $player->name }}? This only works if they have no entries."
                                        >
                                            Remove
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            @endif
                        </div>
                    </div>

                    @if ($canManage && $table->isOpen())
                        <div class="mt-2 flex gap-2">
                            <flux:button size="xs" class="flex-1" wire:click="$dispatch('open-entry', { playerId: {{ $player->id }}, type: 'buy_in' })">
                                + Buy-in
                            </flux:button>
                            <flux:button size="xs" variant="ghost" class="flex-1" wire:click="$dispatch('open-entry', { playerId: {{ $player->id }}, type: 'cash_out' })">
                                Cash out
                            </flux:button>
                        </div>
                    @endif
                </div>
            @empty
                <mds:empty-state icon="user-multiple" title="No players yet" description="Add the first player to start recording buy-ins." />
            @endforelse
        </div>
    @endif

    {{-- entry history, newest first (FR-13/FR-14) --}}
    @php($entries = $this->entries())
    @if ($entries->isNotEmpty())
        <details class="mt-4 rounded-xl border border-zinc-200 bg-white">
            <summary class="cursor-pointer select-none p-3 text-sm font-medium text-zinc-600">
                Entry history ({{ $entries->count() }})
            </summary>
            <div class="divide-y divide-zinc-100 border-t border-zinc-100">
                @foreach ($entries as $entry)
                    <div class="flex items-center justify-between gap-2 p-3 text-sm" wire:key="entry-{{ $entry->id }}">
                        <div class="min-w-0">
                            <span class="font-medium">{{ $entry->player->name }}</span>
                            {{ $entry->type->value === 'buy_in' ? 'bought in' : 'cashed out' }}
                            <x-money :amount="$entry->amount" size="sm" />
                            @if ($entry->note)
                                <div class="truncate text-xs text-zinc-400">{{ $entry->note }}</div>
                            @endif
                        </div>

                        @if ($canManage && $table->isOpen())
                            <div class="flex shrink-0 gap-1">
                                <flux:button size="xs" variant="ghost" wire:click="$dispatch('edit-entry', { entryId: {{ $entry->id }} })">
                                    Edit
                                </flux:button>
                                <flux:button
                                    size="xs" variant="ghost"
                                    wire:click="$dispatch('delete-entry', { entryId: {{ $entry->id }} })"
                                    wire:confirm="Delete this entry?"
                                >
                                    Delete
                                </flux:button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </details>
    @endif

    @unless ($canManage)
        <flux:text size="sm" class="mt-4 text-center text-zinc-400">Read-only view</flux:text>
    @endunless
</div>
