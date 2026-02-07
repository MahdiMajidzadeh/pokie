@extends('layouts.app')

@section('title', $table->name)

@section('content')
    <div class="mb-6">
        <a href="{{ route('home') }}" class="text-amber-600 hover:underline text-sm">← Home</a>
    </div>

    <h1 class="text-2xl font-bold mb-2">{{ $table->name }}</h1>

    @if($isManager)
        <p class="text-sm text-amber-700 mb-4">You are managing this table. Save this page URL to add players and record buy-ins or paybacks later.</p>
    @else
        <p class="text-sm text-stone-600 mb-4">View only. Use the manager link to add players or record transactions.</p>
    @endif

    <div class="bg-white rounded-lg border border-stone-200 p-4 mb-6 space-y-3">
        <div class="grid grid-cols-[1fr_auto] gap-x-6 gap-y-1 items-baseline max-w-md">
            <p class="text-lg font-semibold">Table Balance <span class="text-sm font-normal text-stone-500">(total buy-ins)</span></p>
            <span class="font-mono text-lg text-right tabular-nums">{{ number_format($table->table_balance, 0) }}</span>
            <p class="text-lg font-semibold">Bank</p>
            <span class="font-mono text-lg text-right tabular-nums">{{ number_format($table->bank, 0) }}</span>
        </div>
        <div>
            <p class="text-sm font-semibold text-stone-600 mb-1">All paybacks</p>
            @if($table->paybacks->isEmpty())
                <p class="text-sm text-stone-500">No paybacks yet.</p>
            @else
                <ul class="text-sm space-y-1">
                    @foreach($table->paybacks as $payback)
                        <li class="grid grid-cols-[1fr_auto_auto] gap-4 items-center">
                            <span>{{ $payback->player->name ?? '—' }}</span>
                            <span class="font-mono text-right tabular-nums">{{ number_format($payback->amount, 0) }}</span>
                            <span class="text-stone-400">{{ $payback->created_at->timezone('Asia/Tehran')->format('M j, H:i') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <h2 class="text-lg font-semibold mb-2">Players</h2>
    @if($table->players->isEmpty())
        <p class="text-stone-500 mb-4">No players yet.</p>
    @else
        <ul class="space-y-2 mb-6">
            @foreach($table->players as $player)
                <li class="grid grid-cols-[1fr_auto] gap-4 items-center bg-white rounded border border-stone-200 px-4 py-2">
                    <button type="button" onclick="openPlayerModal({{ $player->id }})" class="text-left text-amber-600 hover:underline font-medium cursor-pointer">{{ $player->name }}</button>
                    <span class="font-mono text-right tabular-nums">{{ number_format($player->amount, 0) }}</span>
                </li>
            @endforeach
        </ul>

        {{-- Player records modal --}}
        <div id="player-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden" aria-modal="true">
            <div class="absolute inset-0 bg-black/50" onclick="closePlayerModal()"></div>
            <div class="relative bg-white rounded-lg border border-stone-200 shadow-xl max-w-md w-full max-h-[80vh] overflow-hidden flex flex-col">
                <div class="p-4 border-b border-stone-200 flex items-center justify-between">
                    <h3 id="player-modal-title" class="text-lg font-semibold"></h3>
                    <button type="button" onclick="closePlayerModal()" class="text-stone-400 hover:text-stone-600 p-1" aria-label="Close">&times;</button>
                </div>
                <div class="p-4 overflow-y-auto flex-1">
                    @foreach($table->players as $player)
                        <div id="player-content-{{ $player->id }}" class="player-modal-content hidden" data-player-name="{{ e($player->name) }}">
                            <p class="text-sm text-stone-500 mb-3">Balance: <span class="font-mono tabular-nums">{{ number_format($player->amount, 0) }}</span></p>
                            <p class="text-sm font-semibold text-stone-600 mb-1">All records</p>
                            @if($player->records->isEmpty())
                                <p class="text-sm text-stone-500">No buy-ins or paybacks yet.</p>
                            @else
                                <ul class="space-y-1 text-sm">
                                    @foreach($player->records as $record)
                                        <li class="grid grid-cols-[1fr_auto_auto] gap-4 items-center rounded border border-stone-200 px-3 py-2">
                                            <span class="{{ $record->type === 'buy_in' ? 'text-green-700' : 'text-blue-700' }}">{{ $record->label }}</span>
                                            <span class="font-mono text-right tabular-nums">{{ number_format($record->amount, 0) }}</span>
                                            <span class="text-stone-400">{{ $record->created_at->timezone('Asia/Tehran')->format('M j, H:i') }}</span>
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
        <div class="space-y-6 border-t border-stone-200 pt-6">
            <section>
                <h3 class="font-semibold mb-2">Add player</h3>
                <form action="{{ route('table.players.store', ['token' => $table->token, 'manager_token' => $managerToken]) }}" method="POST" class="flex gap-2">
                    @csrf
                    <input type="text" name="name" required placeholder="Player name"
                           class="flex-1 rounded border border-stone-300 px-3 py-2">
                    <button type="submit" class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700">Add</button>
                </form>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </section>

            <section>
                <h3 class="font-semibold mb-2">Record buy-in</h3>
                @if($table->players->isEmpty())
                    <p class="text-sm text-stone-500 mb-2">Add a player first.</p>
                @endif
                <form action="{{ route('table.buy-ins.store', ['token' => $table->token, 'manager_token' => $managerToken]) }}" method="POST" class="flex flex-wrap gap-2 items-end">
                    @csrf
                    <div class="flex-1 min-w-[120px]">
                        <label for="buyin_player" class="sr-only">Player</label>
                        <select name="player_id" id="buyin_player" required
                                class="w-full rounded border border-stone-300 px-3 py-2">
                            <option value="">Select player</option>
                            @foreach($table->players as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-28">
                        <label for="buyin_amount" class="sr-only">Amount</label>
                        <input type="number" name="amount" id="buyin_amount" required min="0.01" step="0.01" placeholder="Amount"
                               class="w-full rounded border border-stone-300 px-3 py-2">
                    </div>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Add buy-in</button>
                </form>
                @error('player_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @error('amount')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </section>

            <section>
                <h3 class="font-semibold mb-2">Record payback to bank</h3>
                @if($table->players->isEmpty())
                    <p class="text-sm text-stone-500 mb-2">Add a player first.</p>
                @endif
                <form action="{{ route('table.paybacks.store', ['token' => $table->token, 'manager_token' => $managerToken]) }}" method="POST" class="flex flex-wrap gap-2 items-end">
                    @csrf
                    <div class="flex-1 min-w-[120px]">
                        <label for="payback_player" class="sr-only">Player</label>
                        <select name="player_id" id="payback_player" required
                                class="w-full rounded border border-stone-300 px-3 py-2">
                            <option value="">Select player</option>
                            @foreach($table->players as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-28">
                        <label for="payback_amount" class="sr-only">Amount</label>
                        <input type="number" name="amount" id="payback_amount" required min="0.01" step="0.01" placeholder="Amount"
                               class="w-full rounded border border-stone-300 px-3 py-2">
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add payback</button>
                </form>
                @error('player_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @error('amount')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </section>
        </div>
    @endif
@endsection
