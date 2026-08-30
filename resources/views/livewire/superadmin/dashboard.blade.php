@section('title', 'Superadmin — All tables')

<div>
    <div class="mx-auto flex w-full max-w-4xl items-center gap-3 px-6 pt-5 sm:px-10 sm:pt-6">
        <a href="{{ route('home') }}" class="text-[15px] font-semibold text-zinc-400 transition hover:text-zinc-600">Pokie</a>
        <span class="text-zinc-200">/</span>
        <span class="text-[15px] font-semibold tracking-tight text-zinc-900">Superadmin</span>
        <flux:spacer />
        <flux:button size="sm" variant="filled" wire:click="logout">Log out</flux:button>
    </div>

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-4 px-6 pb-14 pt-6 sm:px-10 sm:gap-5 sm:pt-8">
        @if($this->tables->total() === 0)
            <div class="flex items-center justify-center rounded-2xl bg-zinc-50 px-6 py-20">
                <mds:empty-state icon="table-cells" title="No tables yet" description="Tables will appear here as soon as someone creates one." />
            </div>
        @else
            <div class="flex items-baseline gap-2">
                <div class="text-[22px] font-semibold tracking-tight text-zinc-900 sm:text-[26px]">All tables</div>
                <span class="text-[13px] tabular-nums text-zinc-400 sm:text-sm">{{ $this->tables->total() }}</span>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Table</flux:table.column>
                    <flux:table.column>Created</flux:table.column>
                    <flux:table.column align="end">Players</flux:table.column>
                    <flux:table.column align="end">Entries</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($this->tables as $table)
                        @php
                            $entries = ($table->buy_ins_count ?? 0) + ($table->paybacks_count ?? 0) + ($table->settlements_count ?? 0);
                            $created = $table->created_at->timezone('Asia/Tehran')->format('M j, Y');
                        @endphp
                        <flux:table.row wire:key="table-{{ $table->id }}">
                            <flux:table.cell variant="strong" class="max-w-52 truncate">{{ $table->name }}</flux:table.cell>
                            <flux:table.cell class="whitespace-nowrap">{{ $created }}</flux:table.cell>
                            <flux:table.cell align="end" class="tabular-nums">{{ $table->players_count }}</flux:table.cell>
                            <flux:table.cell align="end" class="tabular-nums">{{ $entries }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button size="xs" variant="filled" href="{{ route('table.show', ['token' => $table->token]) }}" aria-label="Open {{ $table->name }}">Open</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <flux:pagination :paginator="$this->tables" />
        @endif
    </div>
</div>
