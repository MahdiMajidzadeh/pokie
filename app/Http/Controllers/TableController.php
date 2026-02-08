<?php

namespace App\Http\Controllers;

use App\Models\BuyIn;
use App\Models\Payback;
use App\Models\Player;
use App\Models\Settlement;
use App\Models\Table;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class TableController extends Controller
{
    private const RECENT_TABLES_COOKIE = 'pokie_recent_tables';

    private const RECENT_TABLES_MAX = 10;

    private function findTable(string $token): Table
    {
        return Table::where('token', $token)->firstOrFail();
    }

    private function ensureManager(Table $table, string $managerToken): bool
    {
        return $table->manager_token === $managerToken;
    }

    private function pushRecentTableCookie(Request $request, Table $table, ?string $managerToken = null): void
    {
        $recent = json_decode($request->cookie(self::RECENT_TABLES_COOKIE, '[]'), true) ?: [];
        $entry = [
            'token' => $table->token,
            'name' => $table->name,
        ];
        if ($managerToken !== null) {
            $entry['manager_token'] = $managerToken;
        }
        $recent = array_values(array_filter($recent, fn ($e) => ($e['token'] ?? '') !== $table->token));
        array_unshift($recent, $entry);
        $recent = array_slice($recent, 0, self::RECENT_TABLES_MAX);
        Cookie::queue(self::RECENT_TABLES_COOKIE, json_encode($recent), 60 * 24 * 365);
    }

    public function show(Request $request, string $token): View|RedirectResponse
    {
        $table = $this->findTable($token);
        $table->load(['players.buyIns', 'players.paybacks', 'players.settlements', 'paybacks.player']);

        $this->pushRecentTableCookie($request, $table, null);

        return view('table.show', [
            'table' => $table,
            'isManager' => false,
            'managerToken' => null,
        ]);
    }

    public function showManager(Request $request, string $token, string $managerToken): View|RedirectResponse
    {
        $table = $this->findTable($token);

        if (! $this->ensureManager($table, $managerToken)) {
            return redirect()->route('table.show', ['token' => $token])
                ->with('error', 'Invalid manager link.');
        }

        $table->load(['players.buyIns', 'players.paybacks', 'players.settlements', 'paybacks.player']);

        $this->pushRecentTableCookie($request, $table, $managerToken);

        return view('table.show', [
            'table' => $table,
            'isManager' => true,
            'managerToken' => $managerToken,
        ]);
    }

    public function storePlayer(Request $request, string $token, string $managerToken): RedirectResponse
    {
        $table = $this->findTable($token);

        if (! $this->ensureManager($table, $managerToken)) {
            return redirect()->route('table.show', ['token' => $token])
                ->with('error', 'Invalid manager link.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $table->players()->create(['name' => $validated['name']]);

        return redirect()->route('table.manager', ['token' => $token, 'manager_token' => $managerToken])
            ->with('success', 'Player added.');
    }

    public function storeBuyIn(Request $request, string $token, string $managerToken): RedirectResponse
    {
        $table = $this->findTable($token);

        if (! $this->ensureManager($table, $managerToken)) {
            return redirect()->route('table.show', ['token' => $token])
                ->with('error', 'Invalid manager link.');
        }

        $validated = $request->validate([
            'player_id' => ['required', 'exists:players,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $player = Player::findOrFail($validated['player_id']);
        if ($player->table_id !== $table->id) {
            abort(403);
        }

        BuyIn::create([
            'table_id' => $table->id,
            'player_id' => $player->id,
            'amount' => -abs((float) $validated['amount']),
        ]);

        return redirect()->route('table.manager', ['token' => $token, 'manager_token' => $managerToken])
            ->with('success', 'Buy-in recorded.');
    }

    public function storePayback(Request $request, string $token, string $managerToken): RedirectResponse
    {
        $table = $this->findTable($token);

        if (! $this->ensureManager($table, $managerToken)) {
            return redirect()->route('table.show', ['token' => $token])
                ->with('error', 'Invalid manager link.');
        }

        $validated = $request->validate([
            'player_id' => ['required', 'exists:players,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $player = Player::findOrFail($validated['player_id']);
        if ($player->table_id !== $table->id) {
            abort(403);
        }

        Payback::create([
            'table_id' => $table->id,
            'player_id' => $player->id,
            'amount' => abs((float) $validated['amount']),
        ]);

        return redirect()->route('table.manager', ['token' => $token, 'manager_token' => $managerToken])
            ->with('success', 'Payback recorded.');
    }

    public function storeSettlement(Request $request, string $token, string $managerToken): RedirectResponse
    {
        $table = $this->findTable($token);

        if (! $this->ensureManager($table, $managerToken)) {
            return redirect()->route('table.show', ['token' => $token])
                ->with('error', 'Invalid manager link.');
        }

        $validated = $request->validate([
            'player_id' => ['required', 'exists:players,id'],
            'amount' => ['required', 'numeric'],
        ]);

        $player = Player::findOrFail($validated['player_id']);
        if ($player->table_id !== $table->id) {
            abort(403);
        }

        Settlement::create([
            'table_id' => $table->id,
            'player_id' => $player->id,
            'amount' => (float) $validated['amount'],
        ]);

        return redirect()->route('table.manager', ['token' => $token, 'manager_token' => $managerToken])
            ->with('success', 'Settlement recorded.');
    }
}
