@extends('layouts.app')

@section('title', $table->name)

@section('content')
    <div class="mb-6">
        <a href="{{ route('home') }}" class="text-accent hover:underline text-sm">← Home</a>
    </div>

    <div class="flex flex-wrap items-center gap-2 mb-4">
        <h1 class="text-2xl font-bold">{{ $table->name }}</h1>
        <button type="button" onclick="copyTableUrl(this)" data-table-url="{{ url()->route('table.show', ['token' => $table->token]) }}" data-copy-label="public link"
                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded text-sm font-medium text-foreground bg-muted hover:bg-secondary border border-border transition-colors"
                title="Copy public link">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
            </svg>
            <span class="copy-label">public link</span>
        </button>
        @if($isManager && $managerToken)
            <button type="button" onclick="copyTableUrl(this)" data-table-url="{{ url()->route('table.manager', ['token' => $table->token, 'manager_token' => $managerToken]) }}" data-copy-label="manager link"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded text-sm font-medium text-foreground bg-muted hover:bg-secondary border border-border transition-colors"
                    title="Copy manager link">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                </svg>
                <span class="copy-label">manager link</span>
            </button>
        @endif
        @if($isManager)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-muted text-foreground border border-border">admin mode</span>
        @endif
    </div>
    <script>
        function copyTableUrl(btn) {
            const url = btn.getAttribute('data-table-url');
            const defaultLabel = btn.getAttribute('data-copy-label') || 'Copy link';
            navigator.clipboard.writeText(url).then(function() {
                const label = btn.querySelector('.copy-label');
                if (label) { label.textContent = 'Copied!'; }
                btn.classList.add('text-accent');
                setTimeout(function() {
                    if (label) { label.textContent = defaultLabel; }
                    btn.classList.remove('text-accent');
                }, 2000);
            });
        }
    </script>

    <div class="bg-card rounded border border-border p-4 mb-6 space-y-3">
        <div class="grid grid-cols-[1fr_auto] gap-x-6 gap-y-1 items-baseline max-w-md">
            <p class="text-lg font-semibold">Table Balance <span class="text-sm font-normal text-muted-foreground">(total buy-ins)</span></p>
            <span class="font-mono text-lg text-right tabular-nums text-accent">{{ number_format(abs($table->table_balance), 0) }}</span>
            <p class="text-lg font-semibold">Bank</p>
            <span class="font-mono text-lg text-right tabular-nums">{{ number_format($table->bank, 0) }}</span>
        </div>
        <div>
            <p class="text-sm font-semibold text-muted-foreground mb-1">All paybacks</p>
            @if($table->paybacks->isEmpty())
                <p class="text-sm text-muted-foreground">No paybacks yet.</p>
            @else
                <ul class="text-sm space-y-1">
                    @foreach($table->paybacks as $payback)
                        <li class="grid grid-cols-[1fr_auto_auto] gap-4 items-center">
                            <span>{{ $payback->player->name ?? '—' }}</span>
                            <span class="font-mono text-right tabular-nums text-accent">+{{ number_format($payback->amount, 0) }}</span>
                            <span class="text-muted-foreground">{{ $payback->created_at->timezone('Asia/Tehran')->format('M j, H:i') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <h2 class="text-lg font-semibold mb-2">Players</h2>
    @if($table->players->isEmpty())
        <p class="text-muted-foreground mb-4">No players yet.</p>
    @else
        <ul class="space-y-2 mb-6">
            @foreach($table->players as $player)
                <li class="grid grid-cols-[1fr_auto] gap-4 items-center bg-card rounded border border-border px-4 py-2">
                    <button type="button" onclick="openPlayerModal({{ $player->id }})" class="text-left text-accent hover:underline font-medium cursor-pointer">{{ $player->name }}</button>
                    <span class="font-mono text-right tabular-nums">{{ number_format(($player->amount), 0) }}</span>
                </li>
            @endforeach
        </ul>

        {{-- Player records modal --}}
        <div id="player-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden" aria-modal="true">
            <div class="absolute inset-0 bg-black/50" onclick="closePlayerModal()"></div>
            <div class="relative bg-card rounded border border-border shadow-xl max-w-md w-full max-h-[80vh] overflow-hidden flex flex-col">
                <div class="p-4 border-b border-border flex items-center justify-between">
                    <h3 id="player-modal-title" class="text-lg font-semibold"></h3>
                    <button type="button" onclick="closePlayerModal()" class="text-muted-foreground hover:text-foreground p-1" aria-label="Close">&times;</button>
                </div>
                <div class="p-4 overflow-y-auto flex-1">
                    @foreach($table->players as $player)
                        <div id="player-content-{{ $player->id }}" class="player-modal-content hidden" data-player-name="{{ e($player->name) }}">
                            <p class="text-sm text-muted-foreground mb-3">Balance: <span class="font-mono tabular-nums">{{ number_format(abs($player->amount), 0) }}</span></p>
                            <p class="text-sm font-semibold text-muted-foreground mb-1">All records</p>
                            @if($player->records->isEmpty())
                                <p class="text-sm text-muted-foreground">No buy-ins or paybacks yet.</p>
                            @else
                                <ul class="space-y-1 text-sm">
                                    @foreach($player->records as $record)
                                        <li class="grid grid-cols-[1fr_auto_auto] gap-4 items-center rounded border border-border px-3 py-2">
                                            <span class="{{ $record->type === 'buy_in' ? 'text-blue' : 'text-accent' }}">{{ $record->label }}</span>
                                            <span class="font-mono text-right tabular-nums text-accent">{{ $record->amount >= 0 ? '+' : '' }}{{ number_format($record->amount, 0) }}</span>
                                            <span class="text-muted-foreground">{{ $record->created_at->timezone('Asia/Tehran')->format('M j, H:i') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <script>
            function openPlayerModal(playerId) {
                document.querySelectorAll('.player-modal-content').forEach(el => el.classList.add('hidden'));
                const content = document.getElementById('player-content-' + playerId);
                const titleEl = document.getElementById('player-modal-title');
                if (content && titleEl) {
                    titleEl.textContent = content.getAttribute('data-player-name') || 'Player';
                    content.classList.remove('hidden');
                }
                document.getElementById('player-modal').classList.remove('hidden');
            }
            function closePlayerModal() {
                document.getElementById('player-modal').classList.add('hidden');
            }
        </script>
    @endif

    @if($isManager)
        <div class="space-y-6 border-t border-border pt-6">
            <section>
                <h3 class="font-semibold mb-2">Add player</h3>
                <form action="{{ route('table.players.store', ['token' => $table->token, 'manager_token' => $managerToken]) }}" method="POST" class="flex gap-2">
                    @csrf
                    <input type="text" name="name" required placeholder="Player name"
                           class="flex-1 rounded border border-input bg-background px-3 py-2">
                    <button type="submit" class="bg-primary text-primary-foreground px-4 py-2 rounded hover:opacity-90">Add</button>
                </form>
                @error('name')
                    <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                @enderror
            </section>

            <section>
                <h3 class="font-semibold mb-2">Record buy-in</h3>
                @if($table->players->isEmpty())
                    <p class="text-sm text-muted-foreground mb-2">Add a player first.</p>
                @endif
                <form action="{{ route('table.buy-ins.store', ['token' => $table->token, 'manager_token' => $managerToken]) }}" method="POST" class="flex flex-wrap gap-2 items-end">
                    @csrf
                    <div class="flex-1 min-w-[120px]">
                        <label for="buyin_player" class="sr-only">Player</label>
                        <select name="player_id" id="buyin_player" required
                                class="w-full rounded border border-input bg-background px-3 py-2">
                            <option value="">Select player</option>
                            @foreach($table->players as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-28">
                        <label for="buyin_amount" class="sr-only">Amount</label>
                        <input type="number" name="amount" id="buyin_amount" required min="0.01" step="0.01" placeholder="Amount"
                               class="w-full rounded border border-input bg-background px-3 py-2">
                    </div>
                    <button type="submit" class="bg-primary text-primary-foreground px-4 py-2 rounded hover:opacity-90">Add buy-in</button>
                </form>
                @error('player_id')
                    <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                @enderror
                @error('amount')
                    <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                @enderror
            </section>

            <section>
                <h3 class="font-semibold mb-2">Record payback to bank</h3>
                @if($table->players->isEmpty())
                    <p class="text-sm text-muted-foreground mb-2">Add a player first.</p>
                @endif
                <form action="{{ route('table.paybacks.store', ['token' => $table->token, 'manager_token' => $managerToken]) }}" method="POST" class="flex flex-wrap gap-2 items-end">
                    @csrf
                    <div class="flex-1 min-w-[120px]">
                        <label for="payback_player" class="sr-only">Player</label>
                        <select name="player_id" id="payback_player" required
                                class="w-full rounded border border-input bg-background px-3 py-2">
                            <option value="">Select player</option>
                            @foreach($table->players as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-28">
                        <label for="payback_amount" class="sr-only">Amount</label>
                        <input type="number" name="amount" id="payback_amount" required min="0.01" step="0.01" placeholder="Amount"
                               class="w-full rounded border border-input bg-background px-3 py-2">
                    </div>
                    <button type="submit" class="bg-accent text-accent-foreground px-4 py-2 rounded hover:opacity-90">Add payback</button>
                </form>
                @error('player_id')
                    <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                @enderror
                @error('amount')
                    <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                @enderror
            </section>
        </div>
    @endif
@endsection
