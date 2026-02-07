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
        <div class="flex flex-wrap gap-6">
            <p class="text-lg font-semibold">Table Balance: <span class="font-mono">{{ number_format($table->table_balance, 0) }}</span> <span class="text-sm font-normal text-stone-500">(total buy-ins)</span></p>
            <p class="text-lg font-semibold">Bank: <span class="font-mono">{{ number_format($table->bank, 0) }}</span></p>
        </div>
        <div>
            <p class="text-sm font-semibold text-stone-600 mb-1">All paybacks</p>
            @if($table->paybacks->isEmpty())
                <p class="text-sm text-stone-500">No paybacks yet.</p>
            @else
                <ul class="text-sm space-y-1">
                    @foreach($table->paybacks as $payback)
                        <li class="flex justify-between gap-4">
                            <span>{{ $payback->player->name ?? '—' }}</span>
                            <span class="font-mono">{{ number_format($payback->amount, 0) }}</span>
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
                <li class="flex justify-between items-center bg-white rounded border border-stone-200 px-4 py-2">
                    <span>{{ $player->name }}</span>
                    <span class="font-mono">{{ number_format($player->amount, 0) }}</span>
                </li>
            @endforeach
        </ul>
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
