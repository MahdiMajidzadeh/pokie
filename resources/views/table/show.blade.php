@extends('layouts.app')

@section('title', $table->name)

@section('content')
    <div class="mb-4">
        <a href="{{ route('home') }}" class="text-primary text-decoration-none small fw-medium">← Home</a>
    </div>

    {{-- Header card: table name + actions (profile-style layout) --}}
    <div class="card border-0 rounded-3 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-lg-6 col-12 d-flex align-items-center">
                    <div class="col-auto">
                        <div class="avatar avatar-xl bg-primary rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width:4rem;height:4rem;">
                            <i class="bi bi-currency-dollar text-lg"></i>
                        </div>
                    </div>
                    <div class="ms-4 ms-md-5">
                        <h1 class="h2 ls-tight mb-2 fw-bold">{{ $table->name }}</h1>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <button type="button" onclick="copyTableUrl(this)" data-table-url="{{ url()->route('table.show', ['token' => $table->token]) }}" data-copy-label="public link"
                                    class="btn btn-link p-0 text-muted text-sm fw-semibold text-decoration-none border-0 bg-transparent">
                                <i class="bi bi-link-45deg me-1"></i><span class="copy-label">Public link</span>
                            </button>
                            @if($isManager && $managerToken)
                                <button type="button" onclick="copyTableUrl(this)" data-table-url="{{ url()->route('table.manager', ['token' => $table->token, 'manager_token' => $managerToken]) }}" data-copy-label="manager link"
                                        class="btn btn-link p-0 text-muted text-sm fw-semibold text-decoration-none border-0 bg-transparent">
                                    <i class="bi bi-key me-1"></i><span class="copy-label">Manager link</span>
                                </button>
                            @endif
                            @if($isManager)
                                <span class="text-success text-sm fw-semibold">
                                    <i class="bi bi-patch-check-fill me-1"></i>Admin mode
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function copyTableUrl(btn) {
            var url = btn.getAttribute('data-table-url');
            var defaultLabel = btn.getAttribute('data-copy-label') || 'Copy link';
            navigator.clipboard.writeText(url).then(function() {
                var label = btn.querySelector('.copy-label');
                if (label) { label.textContent = 'Copied!'; }
                if (btn.classList.contains('d-inline-flex')) {
                    btn.classList.add('btn-success');
                    btn.classList.remove('btn-primary', 'btn-light');
                }
                setTimeout(function() {
                    if (label) { label.textContent = defaultLabel; }
                    if (btn.classList.contains('d-inline-flex')) {
                        btn.classList.remove('btn-success');
                        if (defaultLabel.indexOf('Manager') !== -1) btn.classList.add('btn-primary');
                        else btn.classList.add('btn-light');
                    }
                }, 2000);
            });
        }
    </script>

    {{-- Summary: stat cards --}}
    @php
        $totalSettlement = $table->settlements()->sum('amount') + $table->table_balance;
        $paybacksSum = $table->paybacks->sum('amount');
    @endphp
    <div class="row row-cols-1 row-cols-sm-3 g-3 g-base mb-4">
        <div class="col">
            <div class="card card-sm border-0 rounded-3 h-100">
                <div class="card-body">
                    <div class="h6 text-body-secondary mb-2">Table Balance</div>
                    <div class="d-flex align-items-center gap-3 gap-md-4">
                        <div class="flex-grow-1 d-flex gap-3 align-items-center">
                            <div>
                                <span class="h2">{{ number_format(abs($table->table_balance), 1) }}</span>
                            </div>
                        
                        </div>
                        <div class="ms-auto">
                            <div class="icon text-primary">
                                <i class="bi bi-cash-coin"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-sm border-0 rounded-3 h-100">
                <div class="card-body">
                    <div class="h6 text-body-secondary mb-2">Total Settlement</div>
                    <div class="d-flex align-items-center gap-3 gap-md-4">
                        <div class="flex-grow-1 d-flex gap-3 align-items-center">
                            <div>
                                <span class="h2">{{ number_format($totalSettlement, 1) }}</span>
                            </div>
                            
                        </div>
                        <div class="ms-auto">
                            <div class="icon text-primary">
                                <i class="bi bi-arrow-left-right"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-sm border-0 rounded-3 shadow-sm h-100" role="button" tabindex="0" onclick="openPaybacksModal()" onkeydown="if(event.key==='Enter'||event.key===' ') { event.preventDefault(); openPaybacksModal(); }" style="cursor: pointer;">
                <div class="card-body">
                    <div class="h6 text-body-secondary mb-2">Bank</div>
                    <div class="d-flex align-items-center gap-3 gap-md-4">
                        <div class="flex-grow-1 d-flex gap-3 align-items-center">
                            <div>
                                <span class="h2">{{ number_format($table->bank, 1) }}</span>
                            </div>
                        </div>
                        <div class="ms-auto">
                            <div class="icon text-primary">
                                <i class="bi bi-safe"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- All paybacks modal (opened by clicking Bank card) --}}
    <div id="paybacks-modal" class="d-none position-fixed top-0 start-0 w-100 h-100 align-items-center justify-content-center p-3" style="z-index: 1050; background: rgba(0,0,0,0.5);">
        <div class="bg-white rounded-3 shadow position-relative w-100" style="max-width: 420px; max-height: 85vh; overflow: hidden; display: flex; flex-direction: column;">
            <div class="d-flex align-items-center justify-content-between p-4 border-bottom">
                <h3 class="h5 fw-bold mb-0">All paybacks</h3>
                <button type="button" onclick="closePaybacksModal()" class="btn btn-link text-body-secondary p-0" style="font-size: 1.5rem; line-height: 1;" aria-label="Close">&times;</button>
            </div>
            <div class="p-4 overflow-auto flex-grow-1">
                @if($table->paybacks->isEmpty())
                    <p class="small text-body-secondary mb-0">No paybacks yet.</p>
                @else
                    <ul class="list-unstyled small mb-0">
                        @foreach($table->paybacks as $payback)
                            <li class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                <span>{{ $payback->player->name ?? '—' }}</span>
                                <span class="text-primary fw-medium">+{{ number_format($payback->amount, 1) }}</span>
                                <span class="text-body-secondary">{{ $payback->created_at->timezone('Asia/Tehran')->format('M j, H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
    <script>
        function openPaybacksModal() {
            var modal = document.getElementById('paybacks-modal');
            if (modal) { modal.classList.remove('d-none'); modal.classList.add('d-flex'); }
        }
        function closePaybacksModal() {
            var modal = document.getElementById('paybacks-modal');
            if (modal) { modal.classList.add('d-none'); modal.classList.remove('d-flex'); }
        }
        document.getElementById('paybacks-modal').addEventListener('click', function(e) { if (e.target === this) closePaybacksModal(); });
    </script>

    {{-- Players --}}
    @if($table->players->isEmpty())
        <div class="card border-0 rounded-3 shadow-sm mb-4">
            <div class="card-body p-4">
                <p class="text-body-secondary mb-0">No players yet.</p>
            </div>
        </div>
    @else
        <div class="card border-0 rounded-3 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pt-0 pb-1 px-4 pt-4">
                <h5 class="fw-bold mb-0">Players</h5>
                <p class="text-body-secondary small mt-1 mb-0">
                    Players at this table and their current balance.
                </p>
            </div>
            <div class="card-body py-0 px-0">
                <div class="list-group list-group-flush">
                    @foreach($table->players as $player)
                        <button type="button" onclick="openPlayerModal({{ $player->id }})" class="list-group-item list-group-item-action border-0 border-bottom d-flex align-items-center justify-content-between gap-3 gap-md-4 py-3 px-4 text-start">
                            <div class="d-flex align-items-center gap-3 col-4">
                                <div class="rounded flex-shrink-0 d-flex align-items-center justify-content-center bg-body-secondary text-body-emphasis border shadow-sm" style="width:2.5rem;height:2.5rem;">
                                    <i class="bi bi-person-fill small"></i>
                                </div>
                                <div>
                                    <span class="d-block text-body-emphasis small fw-semibold">
                                        {{ $player->name }}
                                    </span>
                                </div>
                            </div>
                            <div class="">
                                @if($player->settlements->isNotEmpty())
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill">
                                        Cleared
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill">
                                        Playing
                                    </span>
                                @endif
                            </div>
                            <div class="text-end flex-shrink-0">
                                <span class="d-block text-body-emphasis small fw-bold">
                                    {{ number_format($player->display_amount, 1) }}
                                </span>
                                <span class="d-block text-body-secondary" style="font-size:0.75rem;">
                                    balance
                                </span>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Player modal --}}
        <div id="player-modal" class="d-none position-fixed top-0 start-0 w-100 h-100 align-items-center justify-content-center p-3" style="z-index: 1050; background: rgba(0,0,0,0.5);">
            <div class="bg-white rounded-3 shadow position-relative w-100" style="max-width: 420px; max-height: 85vh; overflow: hidden; display: flex; flex-direction: column;">
                <div class="d-flex align-items-center justify-content-between p-4 border-bottom">
                    <h3 id="player-modal-title" class="h5 fw-bold mb-0"></h3>
                    <button type="button" onclick="closePlayerModal()" class="btn btn-link text-body-secondary p-0" style="font-size: 1.5rem; line-height: 1;" aria-label="Close">&times;</button>
                </div>
                <div class="p-4 overflow-auto flex-grow-1">
                    @foreach($table->players as $player)
                        <div id="player-content-{{ $player->id }}" class="player-modal-content d-none" data-player-name="{{ $player->settlements->isNotEmpty() ? '(Cleared) ' : '' }}{{ e($player->name) }}">
                            <p class="small text-body-secondary mb-3">Balance + Settlement: <span class="fw-bold">{{ number_format($player->display_amount, 1) }}</span></p>
                            <p class="small fw-semibold text-body-secondary mb-2">All records</p>
                            @if($player->records->isEmpty())
                                <p class="small text-body-secondary mb-0">No records yet.</p>
                            @else
                                <ul class="list-unstyled small mb-0">
                                    @foreach($player->records as $record)
                                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                                            <span class="text-primary">{{ $record->label }}</span>
                                            <span class="text-primary">{{ $record->amount >= 0 ? '+' : '' }}{{ number_format($record->amount, 1) }}</span>
                                            <span class="text-body-secondary">{{ $record->created_at->timezone('Asia/Tehran')->format('M j, H:i') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <script>
            function openPlayerModal(playerId) {
                document.querySelectorAll('.player-modal-content').forEach(el => { el.classList.add('d-none'); });
                var content = document.getElementById('player-content-' + playerId);
                var titleEl = document.getElementById('player-modal-title');
                var modal = document.getElementById('player-modal');
                if (content && titleEl && modal) {
                    titleEl.textContent = content.getAttribute('data-player-name') || 'Player';
                    content.classList.remove('d-none');
                    modal.classList.remove('d-none');
                    modal.classList.add('d-flex');
                }
            }
            function closePlayerModal() {
                var modal = document.getElementById('player-modal');
                if (modal) { modal.classList.add('d-none'); modal.classList.remove('d-flex'); }
            }
            document.getElementById('player-modal').addEventListener('click', function(e) { if (e.target === this) closePlayerModal(); });
        </script>
    @endif

    @if($isManager)
        <div class="pt-2">
            <h2 class="h6 fw-bold text-body-secondary text-uppercase small mb-3">Manage table</h2>

            <div class="card border-0 rounded-3 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h3 class="h6 fw-bold mb-3">Add player</h3>
                    <form action="{{ route('table.players.store', ['token' => $table->token, 'manager_token' => $managerToken]) }}" method="POST" class="row g-2 align-items-end">
                        @csrf
                        <div class="col flex-grow-1">
                            <label for="player_name" class="form-label small fw-medium">Player name</label>
                            <input type="text" name="name" id="player_name" required placeholder="Player name"
                                   class="form-control rounded-3 border border-secondary">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary rounded-3 px-4">Add</button>
                        </div>
                    </form>
                    @error('name')
                        <p class="mt-1 small text-danger mb-0">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="card border-0 rounded-3 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h3 class="h6 fw-bold mb-3">Record buy-in</h3>
                    @if($table->players->isEmpty())
                        <p class="small text-body-secondary mb-3">Add a player first.</p>
                    @endif
                    <form action="{{ route('table.buy-ins.store', ['token' => $table->token, 'manager_token' => $managerToken]) }}" method="POST" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-md-5">
                            <label for="buyin_player" class="form-label small fw-medium">Player</label>
                            <select name="player_id" id="buyin_player" required class="form-select rounded-3 border border-secondary">
                                <option value="">Select player</option>
                                @foreach($table->players as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="buyin_amount" class="form-label small fw-medium">Amount</label>
                            <input type="number" name="amount" id="buyin_amount" required min="0.01" step="0.01" placeholder="Amount"
                                   class="form-control rounded-3 border border-secondary">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary rounded-3 w-100">Add buy-in</button>
                        </div>
                    </form>
                    @error('player_id')<p class="mt-1 small text-danger mb-0">{{ $message }}</p>@enderror
                    @error('amount')<p class="mt-1 small text-danger mb-0">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="card border-0 rounded-3 shadow-0 mb-4">
                <div class="card-body p-4">
                    <h3 class="h6 fw-bold mb-3">Record payback to bank</h3>
                    @if($table->players->isEmpty())
                        <p class="small text-body-secondary mb-3">Add a player first.</p>
                    @endif
                    <form action="{{ route('table.paybacks.store', ['token' => $table->token, 'manager_token' => $managerToken]) }}" method="POST" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-md-5">
                            <label for="payback_player" class="form-label small fw-medium">Player</label>
                            <select name="player_id" id="payback_player" required class="form-select rounded-3 border border-secondary">
                                <option value="">Select player</option>
                                @foreach($table->players as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="payback_amount" class="form-label small fw-medium">Amount</label>
                            <input type="number" name="amount" id="payback_amount" required min="0.01" step="0.01" placeholder="Amount"
                                   class="form-control rounded-3 border border-secondary">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary rounded-3 w-100">Add payback</button>
                        </div>
                    </form>
                    @error('player_id')<p class="mt-1 small text-danger mb-0">{{ $message }}</p>@enderror
                    @error('amount')<p class="mt-1 small text-danger mb-0">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="card border-0 rounded-3 shadow-0 mb-4">
                <div class="card-body p-4">
                    <h3 class="h6 fw-bold mb-3">Record settlement</h3>
                    @if($table->players->isEmpty())
                        <p class="small text-body-secondary mb-3">Add a player first.</p>
                    @endif
                    <form action="{{ route('table.settlements.store', ['token' => $table->token, 'manager_token' => $managerToken]) }}" method="POST" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-md-5">
                            <label for="settlement_player" class="form-label small fw-medium">Player</label>
                            <select name="player_id" id="settlement_player" required class="form-select rounded-3 border border-secondary">
                                <option value="">Select player</option>
                                @foreach($table->players as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="settlement_amount" class="form-label small fw-medium">Amount</label>
                            <input type="number" name="amount" id="settlement_amount" required step="0.01" placeholder="Amount (+/−)"
                                   class="form-control rounded-3 border border-secondary">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary rounded-3 w-100">Add settlement</button>
                        </div>
                    </form>
                    @error('player_id')<p class="mt-1 small text-danger mb-0">{{ $message }}</p>@enderror
                    @error('amount')<p class="mt-1 small text-danger mb-0">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    @endif
@endsection
