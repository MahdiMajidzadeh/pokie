<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Models\Entry;
use App\Models\Payment;
use App\Models\Player;
use App\Models\Table;
use App\Support\Ledger;
use App\Support\SettlementText;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * FR-20..FR-23, S3/S4/S5's read side: summary strip, player list, entry
 * history, and (once settled) results + payments. Polls independently of
 * {@see Show} (which owns every modal) so a 5s refresh never disturbs a
 * form the manager is filling in. Destructive row actions use
 * `wire:confirm` (a native browser confirm) rather than a Flux modal,
 * precisely because a modal's open/closed state is not something a
 * component that re-renders on a timer should be responsible for.
 */
class Board extends Component
{
    #[Locked]
    public int $tableId;

    #[Locked]
    public bool $canManage;

    public function table(): Table
    {
        return Table::findOrFail($this->tableId);
    }

    public function ledger(): Ledger
    {
        return Ledger::for($this->table());
    }

    /** @return Collection<int, Player> */
    public function players(): Collection
    {
        return $this->table()->players()->get();
    }

    /** @return Collection<int, Entry> */
    public function entries(): Collection
    {
        return $this->table()->entries()->with('player')->latest()->latest('id')->get();
    }

    /**
     * FR-29/§8.5 "sorting for display": grouped by payer.
     *
     * @return Collection<int, Payment>
     */
    public function payments(): Collection
    {
        return $this->table()->payments()
            ->with(['fromPlayer', 'toPlayer'])
            ->orderBy('from_player_id')
            ->orderBy('id')
            ->get();
    }

    public function paidCount(): int
    {
        return $this->payments()->filter(fn ($payment) => $payment->paid_at !== null)->count();
    }

    /**
     * The settled table as chat-pasteable plain text (see SettlementText).
     */
    public function settlementText(): string
    {
        return SettlementText::for($this->table());
    }

    /**
     * Re-render immediately after a mutation in the parent, instead of
     * waiting for the next poll tick. The method body does nothing — the
     * #[On] listener itself is what makes Livewire re-request and re-render
     * this component; every accessor above reads fresh from the database.
     */
    #[On('table-updated')]
    public function refresh(): void
    {
        //
    }

    public function render()
    {
        return view('livewire.tables.board');
    }
}
