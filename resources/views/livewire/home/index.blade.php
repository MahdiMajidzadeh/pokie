@section('title', 'Pokie — Track the money at poker night')

<div>
    <div class="flex items-center px-6 pt-5 sm:px-10 sm:pt-6">
        <a href="{{ route('home') }}" class="text-[15px] font-semibold tracking-tight text-zinc-900">Pokie</a>
    </div>

    <div class="mx-auto flex w-full max-w-sm flex-col gap-8 px-6 pb-16 pt-12 sm:max-w-[608px] sm:gap-10 sm:pb-24 sm:pt-20">
        <div class="flex flex-col gap-2.5 sm:gap-3 sm:text-center">
            <h1 class="text-[30px] font-semibold leading-[1.15] tracking-tight text-zinc-900 [text-wrap:pretty] sm:text-[42px] sm:leading-[1.1]">Track the money at poker night.</h1>
            <p class="text-[15px] leading-relaxed text-zinc-500 [text-wrap:pretty] sm:text-[17px]">No signup. Make a table, share the link, settle up at the end.</p>
        </div>

        <form wire:submit="createTable" class="flex flex-col gap-2.5">
            <flux:label for="table-name">Table name</flux:label>
            <div class="flex flex-col gap-2.5 sm:flex-row">
                <div class="min-w-0 flex-1">
                    <flux:input id="table-name" wire:model="form.name" placeholder="Friday night at Sam's" />
                </div>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="createTable">
                    <span wire:loading.remove wire:target="createTable">Create table</span>
                    <span wire:loading wire:target="createTable">Creating…</span>
                </flux:button>
            </div>
            <flux:error name="form.name" />
            <p class="text-[13px] text-zinc-400">You'll get a manage link for you and a view link for players.</p>
        </form>

        @if(!empty($recentTables))
            <div class="flex flex-col">
                <x-eyebrow class="pb-2">Recent tables</x-eyebrow>
                @foreach($recentTables as $recent)
                    @php
                        $recentDate = null;
                        if (!empty($recent['at'])) {
                            $at = \Illuminate\Support\Carbon::parse($recent['at'])->timezone('Asia/Tehran');
                            $recentDate = $at->isToday() ? 'Today' : $at->format('M j');
                        }
                    @endphp
                    <div class="flex items-center gap-3 border-t border-zinc-100 py-3.5">
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-[15px] font-medium text-zinc-900">{{ $recent['name'] ?? 'Table' }}</div>
                            @if($recentDate)
                                <div class="text-[13px] text-zinc-400">{{ $recentDate }}</div>
                            @endif
                        </div>
                        <div class="flex shrink-0 gap-1.5">
                            <flux:button
                                size="sm"
                                variant="filled"
                                href="{{ route('table.show', ['token' => $recent['token'] ?? '']) }}"
                                aria-label="Open {{ $recent['name'] ?? 'table' }} as viewer"
                            >View</flux:button>
                            @if(!empty($recent['manager_token']))
                                <flux:button
                                    size="sm"
                                    variant="primary"
                                    href="{{ route('table.manager', ['token' => $recent['token'] ?? '', 'managerToken' => $recent['manager_token']]) }}"
                                    aria-label="Manage {{ $recent['name'] ?? 'table' }}"
                                >Manage</flux:button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
