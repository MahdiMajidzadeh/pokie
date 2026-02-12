@extends('layouts.app')

@section('title', $table->name)

@section('content')
    <div class="mb-4">
        <a href="{{ route('home') }}" class="text-decoration-none small fw-medium" style="color: #2563eb;">← Home</a>
    </div>

    {{-- Header card: table name + actions (profile-style layout) --}}
    <div class="card border-0 rounded-3 shadow-sm mb-4" style="background: #ffffff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.06);">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center">
                <div class="col-lg-6 col-12 d-flex align-items-center">
                    <div class="col-auto">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:4rem;height:4rem; background-color: #2563eb;">
                            <i class="bi bi-currency-dollar text-white" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                    <div class="ms-4 ms-md-5">
                        <h1 class="h2 ls-tight mb-2 fw-bold" style="color: #111827;">{{ $table->name }}</h1>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                                <button type="button" onclick="copyTableUrl(this)" data-table-url="{{ url()->route('table.show', ['token' => $table->token]) }}" data-copy-label="public link"
                                    class="btn btn-link p-0 text-sm fw-semibold text-decoration-none border-0 bg-transparent" style="color: #6b7280;">
                                <i class="bi bi-link-45deg me-1"></i><span class="copy-label">Public link</span>
                            </button>
                            @if(!$isManager)
                                <span class="text-body-secondary text-sm fw-semibold">View only</span>
                            @endif
                            @if($isManager && $managerToken)
                                <button type="button" onclick="copyTableUrl(this)" data-table-url="{{ url()->route('table.manager', ['token' => $table->token, 'manager_token' => $managerToken]) }}" data-copy-label="manager link"
                                        class="btn btn-link p-0 text-sm fw-semibold text-decoration-none border-0 bg-transparent" style="color: #6b7280;">
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
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 g-base mb-4">
        <div class="col">
            <div class="card card-sm border-0 rounded-3 h-100 shadow-sm" style="background: #ffffff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.06);">
                <div class="card-body">
                    <div class="h6 text-body-secondary mb-2">Table Balance</div>
                    <div class="d-flex align-items-center gap-3 gap-md-4">
                        <div class="flex-grow-1 d-flex gap-3 align-items-center">
                            <div>
                                <span class="h2">{{ number_format(abs($table->table_balance), 1) }}</span>
                            </div>
                        
                        </div>
                        <div class="ms-auto">
                            <div class="icon" style="color: #2563eb;">
                                <i class="bi bi-cash-coin"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-sm border-0 rounded-3 h-100 shadow-sm" style="background: #ffffff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.06);">
                <div class="card-body">
                    <div class="h6 text-body-secondary mb-2">Total Settlement</div>
                    <div class="d-flex align-items-center gap-3 gap-md-4">
                        <div class="flex-grow-1 d-flex gap-3 align-items-center">
                            <div>
                                <span class="h2">{{ number_format($totalSettlement, 1) }}</span>
                            </div>
                            
                        </div>
                        <div class="ms-auto">
                            <div class="icon" style="color: #2563eb;">
                                <i class="bi bi-arrow-left-right"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-sm border-0 rounded-3 shadow-sm h-100" style="background: #ffffff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.06);" role="button" tabindex="0" onclick="openPaybacksModal()" onkeydown="if(event.key==='Enter'||event.key===' ') { event.preventDefault(); openPaybacksModal(); }" style="cursor: pointer;">
                <div class="card-body">
                    <div class="h6 text-body-secondary mb-2">Bank</div>
                    <div class="d-flex align-items-center gap-3 gap-md-4">
                        <div class="flex-grow-1 d-flex gap-3 align-items-center">
                            <div>
                                <span class="h2">{{ number_format($table->bank, 1) }}</span>
                            </div>
                        </div>
                        <div class="ms-auto">
                            <div class="icon" style="color: #2563eb;">
                                <i class="bi bi-safe"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-sm border-0 rounded-3 shadow-sm h-100" style="background: #ffffff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.06);" role="button" tabindex="0" onclick="openWithdrawModal()" onkeydown="if(event.key==='Enter'||event.key===' ') { event.preventDefault(); openWithdrawModal(); }" style="cursor: pointer;">
                <div class="card-body">
                    <div class="h6 text-body-secondary mb-2">Withdraw</div>
                    <div class="d-flex align-items-center gap-3 gap-md-4">
                        <div class="flex-grow-1 d-flex gap-3 align-items-center">
                            <div>
                                <span class="h6 fw-semibold" style="color: #2563eb;">Settle up</span>
                            </div>
                        </div>
                        <div class="ms-auto">
                            <div class="icon" style="color: #2563eb;">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- All paybacks modal (opened by clicking Bank card) --}}
    <div id="paybacks-modal" class="d-none position-fixed top-0 start-0 w-100 h-100 align-items-center justify-content-center p-3" style="z-index: 1050; background: rgba(0,0,0,0.5);">
        <div class="bg-white rounded-3 position-relative w-100" style="max-width: 420px; max-height: 85vh; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.05);">
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

    {{-- Withdraw modal (minimum transactions to settle up) --}}
    @php
        $withdrawTransactions = $table->getMinimumSettlementTransactions();
    @endphp
    <div id="withdraw-modal" class="d-none position-fixed top-0 start-0 w-100 h-100 align-items-center justify-content-center p-3" style="z-index: 1050; background: rgba(0,0,0,0.5);">
        <div class="bg-white rounded-3 position-relative w-100" style="max-width: 520px; max-height: 85vh; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.05);">
            <div class="d-flex align-items-center justify-content-between p-4 border-bottom">
                <h3 class="h5 fw-bold mb-0">Withdraw options</h3>
                <button type="button" onclick="closeWithdrawModal()" class="btn btn-link text-body-secondary p-0" style="font-size: 1.5rem; line-height: 1;" aria-label="Close">&times;</button>
            </div>
            <div class="p-4 overflow-auto flex-grow-1">
                @if($withdrawTransactions->isEmpty())
                    <p class="small text-body-secondary mb-0">No payments needed. All balances are settled.</p>
                @else
                    <p class="small text-body-secondary mb-3">Minimum transactions to settle all balances:</p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 small fw-semibold text-body-secondary">From</th>
                                    <th class="border-0 small fw-semibold text-body-secondary">To</th>
                                    <th class="border-0 small fw-semibold text-body-secondary text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($withdrawTransactions as $tx)
                                    <tr>
                                        <td class="small">{{ $tx->from->name }}</td>
                                        <td class="small">{{ $tx->to->name }}</td>
                                        <td class="small text-end fw-medium">{{ number_format($tx->amount, 1) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <script>
        function openWithdrawModal() {
            var modal = document.getElementById('withdraw-modal');
            if (modal) { modal.classList.remove('d-none'); modal.classList.add('d-flex'); }
        }
        function closeWithdrawModal() {
            var modal = document.getElementById('withdraw-modal');
            if (modal) { modal.classList.add('d-none'); modal.classList.remove('d-flex'); }
        }
        document.getElementById('withdraw-modal').addEventListener('click', function(e) { if (e.target === this) closeWithdrawModal(); });
    </script>

    {{-- Players --}}
    @if($table->players->isEmpty())
        <div class="card border-0 rounded-3 shadow-sm mb-4" style="background: #ffffff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.06);">
            <div class="card-body p-5">
                <p class="text-body-secondary mb-0">No players yet.</p>
            </div>
        </div>
    @else
        <div class="card border-0 rounded-3 shadow-sm mb-4" style="background: #ffffff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.06);">
            <div class="card-header bg-transparent border-0 pt-0 pb-1 px-5 pt-5">
                <h5 class="fw-bold mb-0" style="color: #111827;">Players</h5>
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
                                    <span class="badge rounded-pill" style="background-color: #f0fdf4; color: #22c55e; border: 1px solid rgba(34,197,94,0.3);">
                                        <i class="bi bi-check-circle-fill me-1"></i>Cleared
                                    </span>
                                @else
                                    <span class="badge rounded-pill" style="background-color: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb;">
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
            <div class="bg-white rounded-3 position-relative w-100" style="max-width: 420px; max-height: 85vh; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.05);">
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
            <h2 class="h6 fw-bold text-uppercase small mb-4" style="color: #6b7280;">Manage table</h2>

            <div class="card border-0 rounded-3 shadow-sm mb-4" style="background: #ffffff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.06);">
                <div class="card-body p-4 p-lg-5">
                    <h3 class="h6 fw-bold mb-3" style="color: #2563eb;">Add player</h3>
                    <form action="{{ route('table.players.store', ['token' => $table->token, 'manager_token' => $managerToken]) }}" method="POST" class="row g-2 align-items-end">
                        @csrf
                        <div class="col flex-grow-1">
                            <label for="player_name" class="form-label small fw-medium">Player name</label>
                            <input type="text" name="name" id="player_name" required placeholder="Player name"
                                   class="form-control rounded-3 border border-secondary">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary rounded-3 px-4" style="background-color: #2563eb; border-color: #2563eb;">Add</button>
                        </div>
                    </form>
                    @error('name')
                        <p class="mt-1 small text-danger mb-0">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="card border-0 rounded-3 shadow-sm mb-4" style="background: #ffffff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.06);">
                <div class="card-body p-4 p-lg-5">
                    <h3 class="h6 fw-bold mb-3" style="color: #2563eb;">Record buy-in</h3>
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
                            <button type="submit" class="btn btn-primary rounded-3 w-100" style="background-color: #2563eb; border-color: #2563eb;">Add buy-in</button>
                        </div>
                    </form>
                    @error('player_id')<p class="mt-1 small text-danger mb-0">{{ $message }}</p>@enderror
                    @error('amount')<p class="mt-1 small text-danger mb-0">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="card border-0 rounded-3 shadow-sm mb-4" style="background: #ffffff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.06);">
                <div class="card-body p-4 p-lg-5">
                    <h3 class="h6 fw-bold mb-3" style="color: #2563eb;">Record payback to bank</h3>
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
                            <button type="submit" class="btn btn-primary rounded-3 w-100" style="background-color: #2563eb; border-color: #2563eb;">Add payback</button>
                        </div>
                    </form>
                    @error('player_id')<p class="mt-1 small text-danger mb-0">{{ $message }}</p>@enderror
                    @error('amount')<p class="mt-1 small text-danger mb-0">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="card border-0 rounded-3 shadow-sm mb-4" style="background: #ffffff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.06);">
                <div class="card-body p-4 p-lg-5">
                    <h3 class="h6 fw-bold mb-3" style="color: #2563eb;">Record settlement</h3>
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
                            <button type="submit" class="btn btn-primary rounded-3 w-100" style="background-color: #2563eb; border-color: #2563eb;">Add settlement</button>
                        </div>
                    </form>
                    @error('player_id')<p class="mt-1 small text-danger mb-0">{{ $message }}</p>@enderror
                    @error('amount')<p class="mt-1 small text-danger mb-0">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Activity log: buy-ins, paybacks, settlements --}}
            <div class="card border-0 rounded-3 shadow-sm mb-4" style="background: #ffffff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.06);">
                <div class="card-header bg-transparent border-0 pt-0 pb-1 px-4 pt-4">
                    <h5 class="fw-bold mb-0">Activity log</h5>
                    <p class="text-body-secondary small mt-1 mb-0">
                        All buy-ins, paybacks and settlements. You can delete a log to correct mistakes.
                    </p>
                </div>
                <div class="card-body px-0 pb-4">
                    @if(isset($logs) && $logs->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0 ps-4 small fw-semibold text-body-secondary">Type</th>
                                        <th class="border-0 small fw-semibold text-body-secondary">Player</th>
                                        <th class="border-0 small fw-semibold text-body-secondary text-end">Amount</th>
                                        <th class="border-0 small fw-semibold text-body-secondary">Date</th>
                                        <th class="border-0 pe-4 small fw-semibold text-body-secondary text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($logs as $log)
                                        @php
                                            $logFormId = 'log-delete-form-' . $log->type . '-' . $log->id;
                                            $logLabel = $log->type === 'buy_in' ? 'Buy-in' : ($log->type === 'payback' ? 'Payback' : 'Settlement');
                                            $logAmountDisplay = ($log->amount >= 0 ? '+' : '') . number_format($log->amount, 1);
                                        @endphp
                                        <tr>
                                            <td class="ps-4">
                                                @if($log->type === 'buy_in')
                                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Buy-in</span>
                                                @elseif($log->type === 'payback')
                                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Payback</span>
                                                @else
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Settlement</span>
                                                @endif
                                            </td>
                                            <td>{{ $log->player_name }}</td>
                                            <td class="text-end fw-medium">{{ $logAmountDisplay }}</td>
                                            <td class="small text-body-secondary">{{ $log->created_at->timezone('Asia/Tehran')->format('M j, Y H:i') }}</td>
                                            <td class="pe-4 text-end">
                                                @if($log->type === 'buy_in')
                                                    <form id="{{ $logFormId }}" action="{{ route('table.buy-ins.destroy', ['token' => $table->token, 'manager_token' => $managerToken, 'id' => $log->id]) }}" method="POST" class="d-inline log-delete-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-danger log-delete-button"
                                                            data-form-id="{{ $logFormId }}"
                                                            data-log-type="{{ $log->type }}"
                                                            data-log-label="{{ $logLabel }}"
                                                            data-log-player="{{ $log->player_name }}"
                                                            data-log-amount="{{ $logAmountDisplay }}"
                                                            aria-label="Delete"
                                                        >
                                                            Delete
                                                        </button>
                                                    </form>
                                                @elseif($log->type === 'payback')
                                                    <form id="{{ $logFormId }}" action="{{ route('table.paybacks.destroy', ['token' => $table->token, 'manager_token' => $managerToken, 'id' => $log->id]) }}" method="POST" class="d-inline log-delete-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-danger log-delete-button"
                                                            data-form-id="{{ $logFormId }}"
                                                            data-log-type="{{ $log->type }}"
                                                            data-log-label="{{ $logLabel }}"
                                                            data-log-player="{{ $log->player_name }}"
                                                            data-log-amount="{{ $logAmountDisplay }}"
                                                            aria-label="Delete"
                                                        >
                                                            Delete
                                                        </button>
                                                    </form>
                                                @else
                                                    <form id="{{ $logFormId }}" action="{{ route('table.settlements.destroy', ['token' => $table->token, 'manager_token' => $managerToken, 'id' => $log->id]) }}" method="POST" class="d-inline log-delete-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-danger log-delete-button"
                                                            data-form-id="{{ $logFormId }}"
                                                            data-log-type="{{ $log->type }}"
                                                            data-log-label="{{ $logLabel }}"
                                                            data-log-player="{{ $log->player_name }}"
                                                            data-log-amount="{{ $logAmountDisplay }}"
                                                            aria-label="Delete"
                                                        >
                                                            Delete
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="small text-body-secondary mb-0 ps-4">No activity yet.</p>
                    @endif
                </div>
            </div>

            {{-- Log delete confirmation modal --}}
            <div id="log-delete-modal" class="d-none position-fixed top-0 start-0 w-100 h-100 align-items-center justify-content-center p-3" style="z-index: 1060; background: rgba(0,0,0,0.5);">
                <div class="bg-white rounded-3 position-relative w-100" style="max-width: 420px; max-height: 85vh; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.05);">
                    <div class="d-flex align-items-center justify-content-between p-4 border-bottom">
                        <h3 class="h5 fw-bold mb-0">Delete log</h3>
                        <button type="button" id="log-delete-close-btn" class="btn btn-link text-body-secondary p-0" style="font-size: 1.5rem; line-height: 1;" aria-label="Close">&times;</button>
                    </div>
                    <div class="p-4">
                        <p class="small mb-2">You are about to delete this log:</p>
                        <p class="small mb-2">
                            <span class="fw-semibold" id="log-delete-type"></span>
                            for <span class="fw-semibold" id="log-delete-player"></span>
                            (<span class="fw-semibold" id="log-delete-amount"></span>)
                        </p>
                        <p class="small text-body-secondary mb-3">This action cannot be undone.</p>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="log-delete-cancel-btn">Cancel</button>
                            <button type="button" class="btn btn-sm btn-danger" id="log-delete-confirm-btn">Delete</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                (function () {
                    var modal = document.getElementById('log-delete-modal');
                    if (!modal) return;

                    var typeEl = document.getElementById('log-delete-type');
                    var playerEl = document.getElementById('log-delete-player');
                    var amountEl = document.getElementById('log-delete-amount');
                    var confirmBtn = document.getElementById('log-delete-confirm-btn');
                    var cancelBtn = document.getElementById('log-delete-cancel-btn');
                    var closeBtn = document.getElementById('log-delete-close-btn');
                    var currentFormId = null;

                    function openLogDeleteModal(button) {
                        currentFormId = button.getAttribute('data-form-id');
                        if (typeEl) typeEl.textContent = button.getAttribute('data-log-label') || '';
                        if (playerEl) playerEl.textContent = button.getAttribute('data-log-player') || '';
                        if (amountEl) amountEl.textContent = button.getAttribute('data-log-amount') || '';
                        modal.classList.remove('d-none');
                        modal.classList.add('d-flex');
                    }

                    function closeLogDeleteModal() {
                        currentFormId = null;
                        modal.classList.add('d-none');
                        modal.classList.remove('d-flex');
                    }

                    document.querySelectorAll('.log-delete-button').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            openLogDeleteModal(btn);
                        });
                    });

                    if (cancelBtn) {
                        cancelBtn.addEventListener('click', function (e) {
                            e.preventDefault();
                            closeLogDeleteModal();
                        });
                    }

                    if (closeBtn) {
                        closeBtn.addEventListener('click', function (e) {
                            e.preventDefault();
                            closeLogDeleteModal();
                        });
                    }

                    if (confirmBtn) {
                        confirmBtn.addEventListener('click', function (e) {
                            e.preventDefault();
                            if (!currentFormId) {
                                closeLogDeleteModal();
                                return;
                            }
                            var form = document.getElementById(currentFormId);
                            if (form) {
                                form.submit();
                            }
                            closeLogDeleteModal();
                        });
                    }

                    modal.addEventListener('click', function (e) {
                        if (e.target === modal) {
                            closeLogDeleteModal();
                        }
                    });
                })();
            </script>
        </div>
    @endif
@endsection
