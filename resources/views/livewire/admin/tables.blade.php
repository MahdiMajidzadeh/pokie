<div>
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">All tables</flux:heading>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <flux:button type="submit" variant="ghost" size="sm">Log out</flux:button>
        </form>
    </div>

    <flux:input wire:model.live.debounce.400ms="q" placeholder="Search by name or hash…" class="mb-4" />

    <div class="overflow-x-auto rounded-xl border border-zinc-200">
        <flux:table>
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sort === 'name'" :direction="$dir" wire:click="sortBy('name')">Name</flux:table.column>
                <flux:table.column>Hash</flux:table.column>
                <flux:table.column sortable :sorted="$sort === 'status'" :direction="$dir" wire:click="sortBy('status')">Status</flux:table.column>
                <flux:table.column sortable :sorted="$sort === 'players_count'" :direction="$dir" wire:click="sortBy('players_count')" align="end">Players</flux:table.column>
                <flux:table.column sortable :sorted="$sort === 'pot_in'" :direction="$dir" wire:click="sortBy('pot_in')" align="end">Bought in</flux:table.column>
                <flux:table.column sortable :sorted="$sort === 'created_at'" :direction="$dir" wire:click="sortBy('created_at')">Created</flux:table.column>
                <flux:table.column sortable :sorted="$sort === 'last_activity'" :direction="$dir" wire:click="sortBy('last_activity')">Last activity</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($tables as $table)
                    <flux:table.row wire:key="admin-table-{{ $table->id }}">
                        <flux:table.cell variant="strong">{{ $table->name }}</flux:table.cell>
                        <flux:table.cell><code class="text-xs">{{ $table->table_hash }}</code></flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$table->isOpen() ? 'lime' : 'zinc'">{{ $table->status->value }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">{{ $table->players_count }}</flux:table.cell>
                        <flux:table.cell align="end">{{ number_format((int) $table->pot_in) }}</flux:table.cell>
                        <flux:table.cell>{{ $table->created_at->diffForHumans() }}</flux:table.cell>
                        <flux:table.cell>
                            {{ $table->last_activity ? \Illuminate\Support\Carbon::parse($table->last_activity)->diffForHumans() : '—' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-1">
                                <flux:button size="xs" :href="route('table.view', $table->table_hash)" wire:navigate>Open</flux:button>
                                <flux:button size="xs" variant="ghost" wire:click="revealManagerHash({{ $table->id }})">Manager link</flux:button>
                                <flux:button size="xs" variant="danger" wire:click="confirmDelete({{ $table->id }})">Delete</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8">
                            <flux:text class="py-4 text-center">No tables match.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <div class="mt-4">
        <flux:pagination :paginator="$tables" />
    </div>

    <flux:modal name="reveal-manager-hash" class="md:w-96">
        @php($revealTable = $revealingTableId ? \App\Models\Table::find($revealingTableId) : null)
        <div class="space-y-4">
            <flux:heading size="lg">Manager link</flux:heading>
            @if ($revealTable)
                <div class="flex items-center gap-2">
                    <code class="flex-1 truncate rounded-lg bg-zinc-50 px-3 py-2 text-xs">
                        {{ url("/{$revealTable->table_hash}/{$revealTable->manager_hash}") }}
                    </code>
                    <x-copy-button :url="url('/'.$revealTable->table_hash.'/'.$revealTable->manager_hash)" />
                </div>
            @endif
            <flux:modal.close><flux:button class="w-full">Close</flux:button></flux:modal.close>
        </div>
    </flux:modal>

    <flux:modal name="delete-table" class="md:w-96">
        @php($deleteTableRow = $deletingTableId ? \App\Models\Table::find($deletingTableId) : null)
        <form wire:submit="deleteTable" class="space-y-4">
            <flux:heading size="lg">Delete table?</flux:heading>
            @if ($deleteTableRow)
                <flux:text>
                    This permanently deletes "{{ $deleteTableRow->name }}" and everything in it — the only
                    delete-a-table capability in the product (AR-14). Type the table hash to confirm:
                </flux:text>
                <flux:input wire:model="deleteConfirmationInput" :placeholder="$deleteTableRow->table_hash" />
                <flux:error name="deleteConfirmationInput" />
            @endif
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="danger">Delete permanently</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
