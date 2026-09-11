<?php

declare(strict_types=1);

namespace App\Livewire\Tables;

use App\Enums\EntryType;
use App\Livewire\Forms\EntryForm;
use App\Livewire\Forms\PlayerForm;
use App\Models\Player;
use App\Models\Table;
use App\Settlement\ReopenTable;
use App\Settlement\SettlementBlocked;
use App\Settlement\SettleTable;
use App\Support\AdminAudit;
use App\Support\AdminSession;
use App\Support\Hash;
use App\Support\Ledger;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * S2..S5 — the one route that serves the viewer, the manager, and (elevated)
 * the super admin, all from the same URL shape (requirement.md §4.2/§4.4
 * AR-11). Role is derived entirely in mount(): never trust anything else.
 *
 * Owns every modal and mutating action; the summary/player list/entry
 * history/payments live in the sibling {@see Board} component, which polls
 * on its own so a 5s refresh never disturbs a modal open here.
 */
class Show extends Component
{
    #[Locked]
    public Table $table;

    #[Locked]
    public bool $canManage;

    #[Locked]
    public bool $isAdminView;

    public PlayerForm $playerForm;

    public EntryForm $entryForm;

    public function mount(string $tableHash, ?string $managerHash = null): void
    {
        $normalizedTableHash = Hash::normalize($tableHash);

        abort_if($normalizedTableHash === null, 404);

        $table = Table::where('table_hash', $normalizedTableHash)->first();

        abort_if($table === null, 404);

        $viaManagerLink = false;

        if ($managerHash !== null) {
            $normalizedManagerHash = Hash::normalize($managerHash);

            // Never distinguish "wrong manager hash" from "unknown table" —
            // both are a plain 404 (requirement.md §4.2, NFR-3).
            abort_if(
                $normalizedManagerHash === null || ! hash_equals($table->manager_hash, $normalizedManagerHash),
                404
            );

            $viaManagerLink = true;
        }

        $this->table = $table;
        $this->isAdminView = AdminSession::isActive();
        $this->canManage = $viaManagerLink
            || ($this->isAdminView && ! AdminSession::isViewingAsViewer($table->table_hash));
    }

    protected function ensureCanManage(): void
    {
        abort_unless($this->canManage, 403);
    }

    protected function ensureOpen(): void
    {
        abort_unless($this->table->isOpen(), 403);
    }

    /**
     * NFR-2: 60 write actions per table per minute. Returns false (and adds
     * a visible error) when the caller should stop.
     */
    protected function passesWriteRateLimit(): bool
    {
        $key = 'table-write:'.$this->table->table_hash;
        $max = (int) config('ptable.write_rate_limit_per_minute');

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $this->addError('rate', 'Too many changes at once — wait a moment and try again.');

            return false;
        }

        RateLimiter::hit($key, 60);

        return true;
    }

    public function ledger(): Ledger
    {
        return Ledger::for($this->table);
    }

    /** @return Collection<int, Player> */
    public function players(): Collection
    {
        return $this->table->players()->get();
    }

    /**
     * FR-26: players who bought in but haven't recorded any cash-out yet —
     * the simplest reliable "still has an expected stack" signal available,
     * since the app deliberately doesn't model chip counts (§5.2).
     *
     * @return Collection<int, Player>
     */
    public function unclosedPlayers(): Collection
    {
        $ledger = $this->ledger();

        return $this->players()->filter(function (Player $player) use ($ledger) {
            $totals = $ledger->forPlayer($player->id);

            return $totals['total_in'] > 0 && $totals['total_out'] === 0;
        })->values();
    }

    public function canSettle(): bool
    {
        return $this->table->isOpen() && $this->ledger()->playersWithEntries >= 2;
    }

    // ---------------------------------------------------------------
    // Players (FR-5..FR-10)
    // ---------------------------------------------------------------

    public function openAddPlayer(): void
    {
        $this->ensureCanManage();
        $this->playerForm->reset();
        $this->modal('player')->show();
    }

    public function openRenamePlayer(int $playerId): void
    {
        $this->ensureCanManage();

        $player = $this->table->players()->findOrFail($playerId);
        $this->playerForm->editing_id = $player->id;
        $this->playerForm->name = $player->name;
        $this->playerForm->with_buy_in = false;

        $this->modal('player')->show();
    }

    public function savePlayer(): void
    {
        $this->ensureCanManage();
        $this->ensureOpen();

        if (! $this->passesWriteRateLimit()) {
            return;
        }

        $this->playerForm->validate();

        DB::transaction(function (): void {
            if ($this->playerForm->editing_id) {
                $player = $this->table->players()->findOrFail($this->playerForm->editing_id);
                $player->update(['name' => $this->playerForm->name]);

                AdminAudit::log('player.rename', $this->table, ['player_id' => $player->id, 'name' => $player->name]);

                return;
            }

            $position = ((int) $this->table->players()->max('position')) + 1;

            $player = $this->table->players()->create([
                'name' => $this->playerForm->name,
                'position' => $position,
            ]);

            // FR-7: one-tap first buy-in at the table's default amount.
            if ($this->playerForm->with_buy_in && $this->table->default_buy_in) {
                $this->table->entries()->create([
                    'player_id' => $player->id,
                    'type' => EntryType::BuyIn,
                    'amount' => $this->table->default_buy_in,
                ]);
            }

            AdminAudit::log('player.add', $this->table, ['player_id' => $player->id, 'name' => $player->name]);
        });

        $this->playerForm->reset();
        $this->modal('player')->close();
        $this->dispatch('table-updated');
        Flux::toast(text: 'Player saved.', variant: 'success');
    }

    public function removePlayer(int $playerId): void
    {
        $this->ensureCanManage();
        $this->ensureOpen();

        $player = $this->table->players()->findOrFail($playerId);

        // FR-9: blocked, not silently ignored — the manager needs to know why.
        if ($player->entries()->exists()) {
            Flux::toast(
                text: "{$player->name} has recorded entries — remove those first.",
                variant: 'danger'
            );

            return;
        }

        $player->delete();
        AdminAudit::log('player.remove', $this->table, ['player_id' => $playerId]);
        $this->dispatch('table-updated');
        Flux::toast(text: 'Player removed.', variant: 'success');
    }

    public function toggleLeft(int $playerId): void
    {
        $this->ensureCanManage();

        $player = $this->table->players()->findOrFail($playerId);
        $player->update(['has_left' => ! $player->has_left]);

        AdminAudit::log('player.toggle_left', $this->table, [
            'player_id' => $playerId,
            'has_left' => $player->has_left,
        ]);

        $this->dispatch('table-updated');
    }

    #[On('toggle-left')]
    public function onToggleLeft(int $playerId): void
    {
        $this->toggleLeft($playerId);
    }

    #[On('rename-player')]
    public function onRenamePlayer(int $playerId): void
    {
        $this->openRenamePlayer($playerId);
    }

    #[On('remove-player')]
    public function onRemovePlayer(int $playerId): void
    {
        $this->removePlayer($playerId);
    }

    // ---------------------------------------------------------------
    // Entries (FR-11..FR-19)
    // ---------------------------------------------------------------

    public function openEntry(int $playerId, string $type): void
    {
        $this->ensureCanManage();

        $this->entryForm->reset();
        $this->entryForm->player_id = $playerId;
        $this->entryForm->type = $type;

        if ($type === EntryType::BuyIn->value && $this->table->default_buy_in) {
            $this->entryForm->amount = $this->table->default_buy_in;
        }

        $this->modal('entry')->show();
    }

    /**
     * The Board component polls and never triggers modals directly, so it
     * asks the parent via a plain Livewire event instead of a magic $parent
     * reference (not available in this Livewire version).
     */
    #[On('open-entry')]
    public function onOpenEntry(int $playerId, string $type): void
    {
        $this->openEntry($playerId, $type);
    }

    public function openEditEntry(int $entryId): void
    {
        $this->ensureCanManage();

        $entry = $this->table->entries()->findOrFail($entryId);
        $this->entryForm->editing_id = $entry->id;
        $this->entryForm->player_id = $entry->player_id;
        $this->entryForm->type = $entry->type->value;
        $this->entryForm->amount = $entry->amount;
        $this->entryForm->note = $entry->note;
        $this->entryForm->mark_left = false;

        $this->modal('entry')->show();
    }

    #[On('edit-entry')]
    public function onEditEntry(int $entryId): void
    {
        $this->openEditEntry($entryId);
    }

    public function saveEntry(): void
    {
        $this->ensureCanManage();
        $this->ensureOpen();

        if (! $this->passesWriteRateLimit()) {
            return;
        }

        $this->entryForm->validate();

        DB::transaction(function (): void {
            if ($this->entryForm->editing_id) {
                $entry = $this->table->entries()->findOrFail($this->entryForm->editing_id);
                $entry->update([
                    'player_id' => $this->entryForm->player_id,
                    'type' => $this->entryForm->type,
                    'amount' => $this->entryForm->amount,
                    'note' => $this->entryForm->note,
                ]);

                AdminAudit::log('entry.update', $this->table, ['entry_id' => $entry->id]);
            } else {
                $entry = $this->table->entries()->create([
                    'player_id' => $this->entryForm->player_id,
                    'type' => $this->entryForm->type,
                    'amount' => $this->entryForm->amount,
                    'note' => $this->entryForm->note,
                ]);

                AdminAudit::log('entry.add', $this->table, [
                    'entry_id' => $entry->id,
                    'type' => $entry->type->value,
                    'amount' => $entry->amount,
                ]);
            }

            // FR-19: "and mark as left" one-tap option on a cash-out.
            if ($this->entryForm->mark_left) {
                Player::whereKey($this->entryForm->player_id)->update(['has_left' => true]);
            }
        });

        $this->entryForm->reset();
        $this->modal('entry')->close();
        $this->dispatch('table-updated');
        Flux::toast(text: 'Entry saved.', variant: 'success');
    }

    public function deleteEntry(int $entryId): void
    {
        $this->ensureCanManage();
        $this->ensureOpen();

        $entry = $this->table->entries()->findOrFail($entryId);
        $entry->delete();

        AdminAudit::log('entry.delete', $this->table, ['entry_id' => $entryId]);
        $this->dispatch('table-updated');
        Flux::toast(text: 'Entry deleted.', variant: 'success');
    }

    #[On('delete-entry')]
    public function onDeleteEntry(int $entryId): void
    {
        $this->deleteEntry($entryId);
    }

    // ---------------------------------------------------------------
    // Settlement (FR-24..FR-32, §6)
    // ---------------------------------------------------------------

    public function settle(): void
    {
        $this->ensureCanManage();

        try {
            $this->table = (new SettleTable)($this->table);
            AdminAudit::log('table.settle', $this->table);
            Flux::toast(text: 'Table settled.', variant: 'success');
        } catch (SettlementBlocked $e) {
            Flux::toast(text: $e->getMessage(), variant: 'danger');
        }

        $this->modal('settle')->close();
        $this->dispatch('table-updated');
    }

    public function reopen(): void
    {
        $this->ensureCanManage();

        $this->table = (new ReopenTable)($this->table);
        AdminAudit::log('table.reopen', $this->table);

        $this->modal('reopen')->close();
        $this->dispatch('table-updated');
        Flux::toast(text: 'Table reopened.', variant: 'success');
    }

    public function togglePaid(int $paymentId): void
    {
        $this->ensureCanManage();
        abort_unless($this->table->isSettled(), 403);

        $payment = $this->table->payments()->findOrFail($paymentId);
        $payment->update(['paid_at' => $payment->paid_at ? null : now()]);

        AdminAudit::log('payment.toggle', $this->table, [
            'payment_id' => $paymentId,
            'paid' => $payment->paid_at !== null,
        ]);

        $this->dispatch('table-updated');
    }

    #[On('toggle-paid')]
    public function onTogglePaid(int $paymentId): void
    {
        $this->togglePaid($paymentId);
    }

    // ---------------------------------------------------------------
    // Admin elevation (AR-11..AR-13)
    // ---------------------------------------------------------------

    public function toggleAdminViewer(): void
    {
        abort_unless($this->isAdminView, 403);

        AdminSession::toggleViewerMode($this->table->table_hash);
        $this->canManage = ! AdminSession::isViewingAsViewer($this->table->table_hash);
    }

    public function render()
    {
        $title = ($this->table->name ?: 'pTable').' — pTable';

        return view('livewire.tables.show')->title($title);
    }
}
