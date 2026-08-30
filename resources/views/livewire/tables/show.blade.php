@use('Illuminate\Support\Str')

@section('title', $table->name . ' — Pokie')

@php
    $tz = 'Asia/Tehran';
    $players = $this->players;
    $hasActivity = $this->hasActivity;
    $isNew = $players->isEmpty() && ! $hasActivity;
    $startedAt = $table->created_at->timezone($tz);
    $metaText = $isNew ? 'just created' : 'started ' . ($startedAt->isToday() ? $startedAt->format('g:i A') : $startedAt->format('M j, g:i A'));
    $logTime = fn ($dt) => $dt->timezone($tz)->isToday() ? $dt->timezone($tz)->format('g:i A') : $dt->timezone($tz)->format('M j, g:i A');
    $logMeta = [
        'buy_in' => ['icon' => 'arrow-down-circle', 'color' => 'text-emerald-600', 'verb' => 'buy-in', 'noun' => 'buy-in', 'action' => 'deleteBuyIn'],
        'payback' => ['icon' => 'arrow-up-circle', 'color' => 'text-red-600', 'verb' => 'payback', 'noun' => 'payback', 'action' => 'deletePayback'],
        'settlement' => ['icon' => 'banknotes', 'color' => 'text-blue-600', 'verb' => 'settled', 'noun' => 'settlement', 'action' => 'deleteSettlement'],
    ];
@endphp

<div>
    {{-- Header --}}
    <div class="mx-auto flex w-full max-w-[1058px] items-start gap-2.5 px-6 pt-6 lg:px-12 lg:pt-7">
        <div class="hidden items-center gap-3 pt-1 lg:flex">
            <a href="{{ route('home') }}" class="text-[15px] font-semibold text-zinc-400 transition hover:text-zinc-600">Pokie</a>
            <span class="text-zinc-200">/</span>
        </div>
        <div class="min-w-0 flex-1">
            <div class="truncate text-xl font-semibold tracking-tight text-zinc-900">{{ $table->name }}</div>
            <div class="mt-0.5 flex items-center gap-2">
                @if($isManager)
                    <flux:badge size="sm" color="blue" icon="key">Managing</flux:badge>
                    <span class="text-[13px] text-zinc-400">{{ $metaText }}</span>
                @else
                    <span class="text-[13px] text-zinc-400">Viewing · {{ $metaText }}</span>
                @endif
            </div>
        </div>
        <div x-data="{ copied: false }" data-share-url="{{ route('table.show', ['token' => $table->token]) }}" class="shrink-0">
            <div x-show="! copied">
                <flux:button
                    icon="share"
                    square
                    variant="filled"
                    aria-label="Share this table"
                    x-on:click="navigator.clipboard.writeText($root.dataset.shareUrl).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                />
            </div>
            <div x-show="copied" x-cloak>
                <flux:button icon="check" square variant="filled" aria-label="Link copied" />
            </div>
        </div>
    </div>

    @if($isManager && $players->isEmpty())
        {{-- Manager · empty table --}}
        <div class="mx-auto flex w-full max-w-lg flex-col gap-6 px-6 py-7">
            <flux:callout color="blue" icon="information-circle">
                <flux:callout.heading>Your table is ready.</flux:callout.heading>
                <flux:callout.text>Share the view link with players — keep the manage link to yourself.</flux:callout.text>
            </flux:callout>

            <form wire:submit="addPlayer" class="flex flex-col gap-2">
                <x-eyebrow>Add player</x-eyebrow>
                <div class="flex gap-2">
                    <div class="min-w-0 flex-1">
                        <flux:input wire:model="playerForm.name" placeholder="Name" />
                    </div>
                    <flux:button type="submit" variant="primary">Add</flux:button>
                </div>
                <flux:error name="playerForm.name" />
            </form>

            <div class="rounded-2xl bg-zinc-50 px-5 py-9">
                <mds:empty-state icon="user-group" title="No players yet" description="Add everyone at the table, then record their first buy-ins." />
            </div>
        </div>
    @else
        <div class="mx-auto flex w-full max-w-[608px] flex-col gap-8 px-6 pb-14 pt-7 {{ $isManager ? 'lg:grid lg:max-w-[1058px] lg:grid-cols-[minmax(0,1fr)_380px] lg:items-start lg:gap-14 lg:px-12 lg:pt-9' : '' }}">
            <div class="flex min-w-0 flex-col gap-8">
                @if(session('invalid_manager'))
                    <flux:callout variant="warning" icon="exclamation-triangle">
                        <flux:callout.heading>This manage link isn't valid</flux:callout.heading>
                        <flux:callout.text>It may have been mistyped or replaced. You can still watch the table, or start a new one.</flux:callout.text>
                        <x-slot name="actions">
                            <flux:button size="sm" href="{{ route('table.show', ['token' => $table->token]) }}">Open view-only</flux:button>
                            <flux:button size="sm" variant="primary" href="{{ route('home') }}">New table</flux:button>
                        </x-slot>
                    </flux:callout>
                @endif

                @if($this->allSettled)
                    <div class="flex flex-col items-center gap-1 rounded-2xl bg-emerald-50 px-5 py-8 text-center">
                        <div class="text-lg font-semibold tracking-tight text-emerald-700">All settled up 🎉</div>
                        <div class="text-sm text-zinc-500">No payments needed — everyone is even.</div>
                    </div>
                @endif

                @if($players->isEmpty())
                    <div class="rounded-2xl bg-zinc-50 px-5 py-9">
                        <mds:empty-state icon="user-group" title="No players yet" description="Ask the host to add players and record the first buy-ins." />
                    </div>
                @else
                    <div class="flex flex-col">
                        <div class="flex items-baseline gap-2 pb-2.5">
                            <x-eyebrow>Standings</x-eyebrow>
                            @if($players->count() > 6)
                                <span class="text-xs text-zinc-400">{{ $players->count() }} players</span>
                            @endif
                        </div>
                        @foreach($players as $player)
                            <div wire:key="player-{{ $player->id }}" class="flex items-center gap-3 border-t border-zinc-100 py-3 lg:py-3.5">
                                <div class="min-w-0 flex-1 truncate text-base text-zinc-900 lg:text-[17px]">{{ $player->name }}</div>
                                @if(abs($player->display_amount) < 0.005)
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-xs text-zinc-400">even</span>
                                        <x-money-balance :amount="0" size="lg" />
                                    </div>
                                @else
                                    <x-money-balance :amount="$player->display_amount" size="lg" />
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if($this->transactions->isNotEmpty())
                        <div class="rounded-2xl bg-zinc-50 p-5 lg:p-6">
                            <x-eyebrow class="pb-2">To settle up</x-eyebrow>
                            <div class="divide-y divide-zinc-200/70">
                                @foreach($this->transactions as $tx)
                                    <div wire:key="tx-{{ $tx->from->id }}-{{ $tx->to->id }}" class="flex items-baseline gap-2.5 py-2.5">
                                        <div class="min-w-0 flex-1 text-[15px] text-zinc-600">{{ $tx->from->name }} pays <span class="font-medium text-zinc-900">{{ $tx->to->name }}</span></div>
                                        <div class="shrink-0"><mds:price :amount="$tx->amount" :decimals="2" /></div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="pt-2.5 text-[13px] text-zinc-400">{{ $this->transactions->count() }} {{ Str::plural('payment', $this->transactions->count()) }} and everyone is even.</div>
                        </div>
                    @endif
                @endif

                @if(! $isManager)
                    <p class="text-center text-[13px] text-zinc-400">Read-only link · ask the host to record money</p>
                @endif
            </div>

            @if($isManager)
                <div class="flex min-w-0 flex-col gap-8">
                    @if($players->isNotEmpty())
                        <div class="flex flex-col gap-3">
                            <x-eyebrow>Record money</x-eyebrow>
                            <div role="group" aria-label="Transaction type" class="grid grid-cols-3 gap-1 rounded-xl bg-zinc-100 p-1">
                                @foreach(['buyin' => 'Buy-in', 'payback' => 'Payback', 'settle' => 'Settle'] as $tab => $label)
                                    <button
                                        type="button"
                                        wire:click="setTab('{{ $tab }}')"
                                        aria-pressed="{{ $activeTab === $tab ? 'true' : 'false' }}"
                                        class="min-h-10 rounded-[9px] text-sm font-medium transition {{ $activeTab === $tab ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-900' }}"
                                    >{{ $label }}</button>
                                @endforeach
                            </div>

                            @if($activeTab === 'buyin' || $activeTab === 'payback')
                                <form wire:submit="{{ $activeTab === 'buyin' ? 'recordBuyIn' : 'recordPayback' }}" class="flex flex-col gap-3.5">
                                    <flux:select wire:model="moneyForm.player_id" label="Player">
                                        @foreach($table->players as $p)
                                            <flux:select.option value="{{ $p->id }}">{{ $p->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:field>
                                        <flux:label>Amount</flux:label>
                                        <flux:input.group>
                                            <flux:input wire:model="moneyForm.amount" inputmode="decimal" placeholder="50.00" />
                                            <flux:input.group.suffix>Toman</flux:input.group.suffix>
                                        </flux:input.group>
                                        <flux:error name="moneyForm.amount" />
                                    </flux:field>
                                    <flux:button type="submit" variant="primary">
                                        {{ $activeTab === 'buyin' ? 'Record buy-in' : 'Record payback' }}
                                    </flux:button>
                                </form>
                            @else
                                <form wire:submit="recordSettlement" class="flex flex-col gap-3.5">
                                    <flux:select wire:model="settlementForm.player_id" label="Player">
                                        @foreach($table->players as $p)
                                            <flux:select.option value="{{ $p->id }}">{{ $p->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:field>
                                        <flux:label>Amount</flux:label>
                                        <flux:input.group>
                                            <flux:input wire:model="settlementForm.amount" inputmode="decimal" placeholder="50.00 or -50.00" />
                                            <flux:input.group.suffix>Toman</flux:input.group.suffix>
                                        </flux:input.group>
                                        <flux:error name="settlementForm.amount" />
                                        <flux:description>Positive if the bank pays them, negative if they pay the bank.</flux:description>
                                    </flux:field>
                                    <flux:button type="submit" variant="primary">Record settlement</flux:button>
                                </form>
                            @endif
                        </div>
                    @endif

                    <form wire:submit="addPlayer" class="flex flex-col gap-2">
                        <x-eyebrow>Add player</x-eyebrow>
                        <div class="flex gap-2">
                            <div class="min-w-0 flex-1">
                                <flux:input wire:model="playerForm.name" placeholder="Name" />
                            </div>
                            <flux:button type="submit" variant="filled">Add</flux:button>
                        </div>
                        <flux:error name="playerForm.name" />
                    </form>

                    <div class="flex flex-col" x-data="{ showOlder: false }">
                        <div class="flex items-baseline gap-2 pb-2.5">
                            <x-eyebrow>Activity</x-eyebrow>
                            @if($this->logs->count() > 6)
                                <span class="text-xs text-zinc-400">{{ $this->logs->count() }} entries</span>
                            @endif
                        </div>
                        @if($this->logs->isEmpty())
                            <p class="border-t border-zinc-100 py-3 text-[13px] text-zinc-400">No activity yet — record the first buy-in above.</p>
                        @else
                            @foreach($this->logs as $log)
                                @php $meta = $logMeta[$log->type]; @endphp
                                <div wire:key="log-{{ $log->type }}-{{ $log->id }}" @if($loop->index >= 6) x-show="showOlder" x-cloak @endif>
                                    <div class="flex items-center gap-3 border-t border-zinc-100 py-2">
                                        <flux:icon :icon="$meta['icon']" variant="mini" class="size-[18px] shrink-0 {{ $meta['color'] }}" />
                                        <div class="min-w-0 flex-1">
                                            <div class="truncate text-[15px] text-zinc-900">{{ $log->player_name }} · {{ $meta['verb'] }}</div>
                                            <div class="text-xs text-zinc-400">{{ $logTime($log->created_at) }}</div>
                                        </div>
                                        <div class="shrink-0"><mds:price :amount="abs($log->amount)" :decimals="2" size="sm" /></div>
                                        <flux:modal.trigger name="delete-{{ $log->type }}-{{ $log->id }}">
                                            <flux:button icon="trash" square size="sm" variant="ghost" aria-label="Delete this {{ $meta['noun'] }}" />
                                        </flux:modal.trigger>
                                    </div>
                                    <flux:modal name="delete-{{ $log->type }}-{{ $log->id }}" class="md:w-96">
                                        <div class="flex flex-col gap-6">
                                            <div>
                                                <flux:heading size="lg">Delete this {{ $meta['noun'] }}?</flux:heading>
                                                <flux:text class="mt-2">Standings will update.</flux:text>
                                            </div>
                                            <div class="flex gap-2">
                                                <flux:spacer />
                                                <flux:modal.close>
                                                    <flux:button variant="ghost">Cancel</flux:button>
                                                </flux:modal.close>
                                                <flux:modal.close>
                                                    <flux:button variant="danger" wire:click="{{ $meta['action'] }}({{ $log->id }})">Delete</flux:button>
                                                </flux:modal.close>
                                            </div>
                                        </div>
                                    </flux:modal>
                                </div>
                            @endforeach
                            @if($this->logs->count() > 6)
                                <div x-show="! showOlder" class="pt-2">
                                    <flux:button size="sm" variant="filled" x-on:click="showOlder = true" class="w-full">
                                        Show {{ $this->logs->count() - 6 }} older {{ Str::plural('entry', $this->logs->count() - 6) }}
                                    </flux:button>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
