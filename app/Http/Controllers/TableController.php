<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBuyInRequest;
use App\Http\Requests\StorePaybackRequest;
use App\Http\Requests\StorePlayerRequest;
use App\Http\Requests\StoreSettlementRequest;
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

        $table->load(['players.buyIns', 'players.paybacks', 'players.settlements', 'paybacks.player', 'buyIns.player', 'settlements.player']);

        $this->pushRecentTableCookie($request, $table, $managerToken);

        $logs = collect()
            ->merge($table->buyIns->map(fn (BuyIn $b) => (object) [
                'type' => 'buy_in',
                'label' => 'Buy-in',
                'id' => $b->id,
                'player_name' => $b->player->name ?? '—',
                'amount' => $b->amount,
                'created_at' => $b->created_at,
            ]))
            ->merge($table->paybacks->map(fn (Payback $p) => (object) [
                'type' => 'payback',
                'label' => 'Payback',
                'id' => $p->id,
                'player_name' => $p->player->name ?? '—',
                'amount' => $p->amount,
                'created_at' => $p->created_at,
            ]))
            ->merge($table->settlements->map(fn (Settlement $s) => (object) [
                'type' => 'settlement',
                'label' => 'Settlement',
                'id' => $s->id,
                'player_name' => $s->player->name ?? '—',
                'amount' => $s->amount,
                'created_at' => $s->created_at,
            ]))
            ->sortByDesc('created_at')
            ->values();

        return view('table.show', [
            'table' => $table,
            'isManager' => true,
            'managerToken' => $managerToken,
            'logs' => $logs,
        ]);
    }

    public function storePlayer(StorePlayerRequest $request, string $token, string $managerToken): RedirectResponse
    {
        $table = $this->findTable($token);

        if (! $this->ensureManager($table, $managerToken)) {
            return redirect()->route('table.show', ['token' => $token])
                ->with('error', 'Invalid manager link.');
        }

        $validated = $request->validated();
        $table->players()->create(['name' => $validated['name']]);

        return redirect()->route('table.manager', ['token' => $token, 'manager_token' => $managerToken])
            ->with('success', 'Player added.');
    }

    public function storeBuyIn(StoreBuyInRequest $request, string $token, string $managerToken): RedirectResponse
    {
        $table = $this->findTable($token);

        if (! $this->ensureManager($table, $managerToken)) {
            return redirect()->route('table.show', ['token' => $token])
                ->with('error', 'Invalid manager link.');
        }

        $validated = $request->validated();
        $player = Player::findOrFail($validated['player_id']);

        BuyIn::create([
            'table_id' => $table->id,
            'player_id' => $player->id,
            'amount' => -abs((float) $validated['amount']),
        ]);

        return redirect()->route('table.manager', ['token' => $token, 'manager_token' => $managerToken])
            ->with('success', 'Buy-in recorded.');
    }

    public function storePayback(StorePaybackRequest $request, string $token, string $managerToken): RedirectResponse
    {
        $table = $this->findTable($token);

        if (! $this->ensureManager($table, $managerToken)) {
            return redirect()->route('table.show', ['token' => $token])
                ->with('error', 'Invalid manager link.');
        }

        $validated = $request->validated();
        $player = Player::findOrFail($validated['player_id']);

        Payback::create([
            'table_id' => $table->id,
            'player_id' => $player->id,
            'amount' => abs((float) $validated['amount']),
        ]);

        return redirect()->route('table.manager', ['token' => $token, 'manager_token' => $managerToken])
            ->with('success', 'Payback recorded.');
    }

    public function storeSettlement(StoreSettlementRequest $request, string $token, string $managerToken): RedirectResponse
    {
        $table = $this->findTable($token);

        if (! $this->ensureManager($table, $managerToken)) {
            return redirect()->route('table.show', ['token' => $token])
                ->with('error', 'Invalid manager link.');
        }

        $validated = $request->validated();
        $player = Player::findOrFail($validated['player_id']);

        Settlement::create([
            'table_id' => $table->id,
            'player_id' => $player->id,
            'amount' => (float) $validated['amount'],
        ]);

        return redirect()->route('table.manager', ['token' => $token, 'manager_token' => $managerToken])
            ->with('success', 'Settlement recorded.');
    }

    public function destroyBuyIn(string $token, string $managerToken, int $id): RedirectResponse
    {
        $table = $this->findTable($token);
        if (! $this->ensureManager($table, $managerToken)) {
            return redirect()->route('table.show', ['token' => $token])->with('error', 'Invalid manager link.');
        }
        $buyIn = BuyIn::where('table_id', $table->id)->findOrFail($id);
        $buyIn->delete();

        return redirect()->route('table.manager', ['token' => $token, 'manager_token' => $managerToken])
            ->with('success', 'Buy-in deleted.');
    }

    public function destroyPayback(string $token, string $managerToken, int $id): RedirectResponse
    {
        $table = $this->findTable($token);
        if (! $this->ensureManager($table, $managerToken)) {
            return redirect()->route('table.show', ['token' => $token])->with('error', 'Invalid manager link.');
        }
        $payback = Payback::where('table_id', $table->id)->findOrFail($id);
        $payback->delete();

        return redirect()->route('table.manager', ['token' => $token, 'manager_token' => $managerToken])
            ->with('success', 'Payback deleted.');
    }

    public function destroySettlement(string $token, string $managerToken, int $id): RedirectResponse
    {
        $table = $this->findTable($token);
        if (! $this->ensureManager($table, $managerToken)) {
            return redirect()->route('table.show', ['token' => $token])->with('error', 'Invalid manager link.');
        }
        $settlement = Settlement::where('table_id', $table->id)->findOrFail($id);
        $settlement->delete();

        return redirect()->route('table.manager', ['token' => $token, 'manager_token' => $managerToken])
            ->with('success', 'Settlement deleted.');
    }
}
