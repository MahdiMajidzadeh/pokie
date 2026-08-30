<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Livewire\Forms\MoneyForm;
use App\Livewire\Forms\PlayerForm;
use App\Livewire\Forms\SettlementForm;
use App\Models\BuyIn;
use App\Models\Payback;
use App\Models\Settlement;
use App\Models\Table;
use App\Support\RecentTables;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Show extends Component
{
    private const VIEWER_RELATIONS = ['players.buyIns', 'players.paybacks', 'players.settlements', 'paybacks.player'];

    private const MANAGER_RELATIONS = [
        'players.buyIns', 'players.paybacks', 'players.settlements',
        'paybacks.player', 'buyIns.player', 'settlements.player',
    ];

    #[Locked]
    public Table $table;

    #[Locked]
    public bool $isManager = false;

    #[Locked]
    public ?string $managerToken = null;

    public string $activeTab = 'buyin';

    public PlayerForm $playerForm;

    public MoneyForm $moneyForm;

    public SettlementForm $settlementForm;

    public function mount(string $token, ?string $managerToken = null): void
    {
        $this->table = Table::where('token', $token)->firstOrFail();

        if ($managerToken === null) {
            $this->loadAsViewer();

            return;
        }

        if (! hash_equals($this->table->manager_token, $managerToken)) {
            session()->flash('invalid_manager', true);
            $this->redirect(route('table.show', ['token' => $token]));

            return;
        }

        $this->loadAsManager($managerToken);
    }

    private function loadAsViewer(): void
    {
        $this->table->load(self::VIEWER_RELATIONS);
        RecentTables::push($this->table, null);
        $this->isManager = false;
    }

    private function loadAsManager(string $managerToken): void
    {
        $this->table->load(self::MANAGER_RELATIONS);
        RecentTables::push($this->table, $managerToken);
        $this->isManager = true;
        $this->managerToken = $managerToken;
        $this->applyDefaultPlayerSelection();
    }

    private function refreshTable(): void
    {
        $this->table->load(self::MANAGER_RELATIONS);
    }

    /**
     * Native <select> elements default to their first option even when
     * nothing has been explicitly chosen yet, but wire:model only syncs a
     * property once the user actually changes the control. Without this,
     * submitting with the very first (and often only) player still visually
     * selected would fail validation because player_id was never set.
     */
    private function applyDefaultPlayerSelection(): void
    {
        $firstPlayerId = $this->table->players->first()?->id;

        $this->moneyForm->player_id ??= $firstPlayerId;
        $this->settlementForm->player_id ??= $firstPlayerId;
    }

    #[Computed]
    public function players()
    {
        return $this->table->players->sortByDesc(fn ($p) => $p->display_amount)->values();
    }

    #[Computed]
    public function hasActivity(): bool
    {
        return $this->table->players->contains(
            fn ($p) => $p->buyIns->isNotEmpty() || $p->paybacks->isNotEmpty() || $p->settlements->isNotEmpty()
        );
    }

    #[Computed]
    public function allSettled(): bool
    {
        return $this->players->isNotEmpty()
            && $this->hasActivity
            && $this->players->every(fn ($p) => abs($p->display_amount) < 0.005);
    }

    #[Computed]
    public function transactions()
    {
        return $this->players->isNotEmpty() ? $this->table->getMinimumSettlementTransactions() : collect();
    }

    #[Computed]
    public function logs()
    {
        if (! $this->isManager) {
            return collect();
        }

        return collect()
            ->merge($this->table->buyIns->map(fn (BuyIn $b) => (object) [
                'type' => 'buy_in',
                'label' => 'Buy-in',
                'id' => $b->id,
                'player_name' => $b->player->name ?? '—',
                'amount' => $b->amount,
                'created_at' => $b->created_at,
            ]))
            ->merge($this->table->paybacks->map(fn (Payback $p) => (object) [
                'type' => 'payback',
                'label' => 'Payback',
                'id' => $p->id,
                'player_name' => $p->player->name ?? '—',
                'amount' => $p->amount,
                'created_at' => $p->created_at,
            ]))
            ->merge($this->table->settlements->map(fn (Settlement $s) => (object) [
                'type' => 'settlement',
                'label' => 'Settlement',
                'id' => $s->id,
                'player_name' => $s->player->name ?? '—',
                'amount' => $s->amount,
                'created_at' => $s->created_at,
            ]))
            ->sortByDesc('created_at')
            ->values();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->moneyForm->reset('amount');
        $this->settlementForm->reset('amount');
        $this->resetErrorBag();
    }

    public function addPlayer(): void
    {
        abort_unless($this->isManager, 403);

        $validated = $this->playerForm->validate();
        $this->table->players()->create(['name' => $validated['name']]);
        $this->refreshTable();
        $this->playerForm->reset();
        $this->applyDefaultPlayerSelection();
        unset($this->players);

        Flux::toast(text: 'Player added.', variant: 'success');
    }

    public function recordBuyIn(): void
    {
        abort_unless($this->isManager, 403);

        $validated = $this->moneyForm->validate();
        BuyIn::create([
            'table_id' => $this->table->id,
            'player_id' => $validated['player_id'],
            'amount' => -abs((float) $validated['amount']),
        ]);
        $this->refreshTable();
        $this->moneyForm->reset('amount');
        unset($this->players, $this->transactions, $this->hasActivity, $this->allSettled, $this->logs);

        Flux::toast(text: 'Buy-in recorded.', variant: 'success');
    }

    public function recordPayback(): void
    {
        abort_unless($this->isManager, 403);

        $validated = $this->moneyForm->validate();
        Payback::create([
            'table_id' => $this->table->id,
            'player_id' => $validated['player_id'],
            'amount' => abs((float) $validated['amount']),
        ]);
        $this->refreshTable();
        $this->moneyForm->reset('amount');
        unset($this->players, $this->transactions, $this->hasActivity, $this->allSettled, $this->logs);

        Flux::toast(text: 'Payback recorded.', variant: 'success');
    }

    public function recordSettlement(): void
    {
        abort_unless($this->isManager, 403);

        $validated = $this->settlementForm->validate();
        Settlement::create([
            'table_id' => $this->table->id,
            'player_id' => $validated['player_id'],
            'amount' => (float) $validated['amount'],
        ]);
        $this->refreshTable();
        $this->settlementForm->reset('amount');
        unset($this->players, $this->transactions, $this->hasActivity, $this->allSettled, $this->logs);

        Flux::toast(text: 'Settlement recorded.', variant: 'success');
    }

    public function deleteBuyIn(int $id): void
    {
        abort_unless($this->isManager, 403);

        BuyIn::where('table_id', $this->table->id)->findOrFail($id)->delete();
        $this->refreshTable();
        unset($this->players, $this->transactions, $this->hasActivity, $this->allSettled, $this->logs);

        Flux::toast(text: 'Buy-in deleted.', variant: 'success');
    }

    public function deletePayback(int $id): void
    {
        abort_unless($this->isManager, 403);

        Payback::where('table_id', $this->table->id)->findOrFail($id)->delete();
        $this->refreshTable();
        unset($this->players, $this->transactions, $this->hasActivity, $this->allSettled, $this->logs);

        Flux::toast(text: 'Payback deleted.', variant: 'success');
    }

    public function deleteSettlement(int $id): void
    {
        abort_unless($this->isManager, 403);

        Settlement::where('table_id', $this->table->id)->findOrFail($id)->delete();
        $this->refreshTable();
        unset($this->players, $this->transactions, $this->hasActivity, $this->allSettled, $this->logs);

        Flux::toast(text: 'Settlement deleted.', variant: 'success');
    }

    public function render()
    {
        return view('livewire.tables.show')->extends('layouts.app');
    }
}
