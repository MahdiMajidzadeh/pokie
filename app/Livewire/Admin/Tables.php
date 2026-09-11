<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\EntryType;
use App\Models\Table;
use App\Support\AdminAudit;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * AR-9..AR-15: the only place in the app more than one table is visible.
 */
#[Title('Admin — pTable')]
class Tables extends Component
{
    use WithPagination;

    private const array SORTABLE_COLUMNS = ['name', 'created_at', 'players_count', 'pot_in', 'last_activity', 'status'];

    #[Url]
    public string $q = '';

    #[Url]
    public string $sort = 'created_at';

    #[Url]
    public string $dir = 'desc';

    public ?int $revealingTableId = null;

    public ?int $deletingTableId = null;

    public string $deleteConfirmationInput = '';

    public function sortBy(string $column): void
    {
        if (! in_array($column, self::SORTABLE_COLUMNS, true)) {
            return;
        }

        if ($this->sort === $column) {
            $this->dir = $this->dir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->dir = 'asc';
        }

        $this->resetPage();
    }

    public function revealManagerHash(int $tableId): void
    {
        $table = Table::findOrFail($tableId);
        $this->revealingTableId = $tableId;
        AdminAudit::log('manager_hash.reveal', $table);
        $this->modal('reveal-manager-hash')->show();
    }

    public function confirmDelete(int $tableId): void
    {
        $this->deletingTableId = $tableId;
        $this->deleteConfirmationInput = '';
        $this->modal('delete-table')->show();
    }

    public function deleteTable(): void
    {
        $table = Table::findOrFail($this->deletingTableId);

        if ($this->deleteConfirmationInput !== $table->table_hash) {
            $this->addError('deleteConfirmationInput', 'Type the table hash exactly to confirm deletion.');

            return;
        }

        AdminAudit::log('table.delete', $table);
        $table->delete();

        $this->deletingTableId = null;
        $this->modal('delete-table')->close();
    }

    public function render()
    {
        $query = Table::query()
            ->withCount('players')
            ->withSum(['entries as pot_in' => fn ($q) => $q->where('type', EntryType::BuyIn->value)], 'amount')
            ->withMax('entries as last_activity', 'created_at');

        if ($this->q !== '') {
            $needle = strtoupper(trim($this->q));

            $query->where(function ($q) use ($needle) {
                $q->where('name', 'like', '%'.$this->q.'%')
                    ->orWhere('table_hash', 'like', '%'.$needle.'%');
            });
        }

        $sort = in_array($this->sort, self::SORTABLE_COLUMNS, true) ? $this->sort : 'created_at';
        $dir = $this->dir === 'asc' ? 'asc' : 'desc';

        $tables = $query->orderBy($sort, $dir)->paginate(25);

        return view('livewire.admin.tables', ['tables' => $tables]);
    }
}
