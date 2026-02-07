<?php

namespace App\Http\Controllers;

use App\Models\BuyIn;
use App\Models\Payback;
use App\Models\Player;
use App\Models\Table;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TableController extends Controller
{
    private function findTable(string $token): Table
    {
        return Table::where('token', $token)->firstOrFail();
    }

    private function ensureManager(Table $table, string $managerToken): bool
    {
        return $table->manager_token === $managerToken;
    }

    public function show(string $token): View|RedirectResponse
    {
        $table = $this->findTable($token);
        $table->load(['players.buyIns', 'players.paybacks', 'paybacks.player']);

        return view('table.show', [
            'table' => $table,
            'isManager' => false,
            'managerToken' => null,
        ]);
    }

    public function showManager(string $token, string $managerToken): View|RedirectResponse
    {
        $table = $this->findTable($token);

        if (! $this->ensureManager($table, $managerToken)) {
            return redirect()->route('table.show', ['token' => $token])
                ->with('error', 'Invalid manager link.');
        }

        $table->load(['players.buyIns', 'players.paybacks', 'paybacks.player']);

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
            'amount' => $validated['amount'],
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
            'amount' => $validated['amount'],
        ]);

        return redirect()->route('table.manager', ['token' => $token, 'manager_token' => $managerToken])
            ->with('success', 'Payback recorded.');
    }
}
