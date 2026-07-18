@extends('layouts.app')

@section('title', $table->name . ' — Pokie')

@php
    $tz = 'Asia/Tehran';
    $money = function ($v, $signed = false) {
        $abs = number_format(abs((float) $v), 2);
        if ($signed) {
            if ($v > 0.004) return '+$' . $abs;
            if ($v < -0.004) return '−$' . $abs;
            return '$' . $abs;
        }
        return '$' . $abs;
    };
    $players = $table->players->sortByDesc(fn ($p) => $p->display_amount)->values();
    $hasActivity = $table->players->contains(fn ($p) => $p->buyIns->isNotEmpty() || $p->paybacks->isNotEmpty() || $p->settlements->isNotEmpty());
    $transactions = $players->isNotEmpty() ? $table->getMinimumSettlementTransactions() : collect();
    $allSettled = $players->isNotEmpty() && $hasActivity && $players->every(fn ($p) => abs($p->display_amount) < 0.005);
    $isNew = $players->isEmpty() && ! $hasActivity;
    $startedAt = $table->created_at->timezone($tz);
    $metaText = $isNew ? 'just created' : 'started ' . ($startedAt->isToday() ? $startedAt->format('g:i A') : $startedAt->format('M j, g:i A'));
    $activeTab = old('form', 'buyin');
    $logTime = fn ($dt) => $dt->timezone($tz)->isToday() ? $dt->timezone($tz)->format('g:i A') : $dt->timezone($tz)->format('M j, g:i A');
@endphp

@section('content')
    <style>
        .t-head { display: flex; align-items: flex-start; gap: 10px; padding: 24px 24px 0; max-width: 1058px; margin: 0 auto; }
        .t-crumb { display: none; align-items: center; gap: 12px; padding-top: 4px; }
        .t-name { font: 600 20px 'Instrument Sans', sans-serif; color: #111827; letter-spacing: -0.015em; }
        .t-meta { display: flex; align-items: center; gap: 6px; margin-top: 3px; }
        .share-btn .share-text { display: none; }
        .t-page { max-width: 608px; margin: 0 auto; padding: 28px 24px 56px; display: flex; flex-direction: column; gap: 32px; }
        .t-col { display: flex; flex-direction: column; gap: 32px; min-width: 0; }
        .stand-row { display: flex; align-items: center; gap: 12px; padding: 13px 0; }
        .stand-name { flex: 1; min-width: 0; font: 400 16px 'Instrument Sans', sans-serif; color: #111827; }
        .stand-amt { font: 600 22px 'Instrument Sans', sans-serif; letter-spacing: -0.01em; flex-shrink: 0; }
        .settle-card { padding: 20px; display: flex; flex-direction: column; gap: 2px; }
        .settle-row { display: flex; align-items: center; gap: 10px; padding: 9px 0; }
        .settle-row + .settle-row { border-top: 1px solid #f0f0f1; }
        .settle-who { flex: 1; font: 400 15px 'Instrument Sans', sans-serif; color: #52525b; }
        .settle-who b { color: #111827; font-weight: 500; }
        .settle-amt { font: 600 17px 'Instrument Sans', sans-serif; color: #111827; }
        .act-row { display: flex; align-items: center; gap: 12px; padding: 11px 0; }
        .act-icon { font-size: 17px; flex-shrink: 0; }
        .act-label { font: 400 15px 'Instrument Sans', sans-serif; color: #111827; }
        .act-time { font: 400 12px 'Instrument Sans', sans-serif; color: #a1a1aa; }
        .act-amt { font: 500 15px 'Instrument Sans', sans-serif; color: #111827; flex-shrink: 0; }
        .act-del { width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border: none; border-radius: 10px; background: transparent; color: #a1a1aa; font-size: 15px; cursor: pointer; flex-shrink: 0; }
        .act-del:hover { background: #fef2f2; color: #dc2626; }
        .act-item.open { background: #fafafa; border-radius: 16px; padding: 3px 14px; margin: 0 -14px; }
        .act-item.open .act-row { border-top-color: transparent; }
        .act-confirm { padding: 12px 0 14px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; border-top: 1px solid #f0f0f1; }
        .act-confirm-text { flex: 1; min-width: 130px; font: 400 13px 'Instrument Sans', sans-serif; color: #71717a; }
        .empty-card { display: flex; flex-direction: column; align-items: center; gap: 6px; text-align: center; padding: 36px 20px; }
        @media (min-width: 960px) {
            .t-head { padding: 28px 48px 0; }
            .t-crumb { display: flex; }
            .share-btn { width: auto; padding: 0 16px; font-size: 14px; }
            .share-btn .share-text { display: inline; }
            .t-page.t-grid { max-width: 1058px; display: grid; grid-template-columns: 1fr 380px; gap: 56px; align-items: start; padding-top: 36px; }
            .stand-row { padding: 15px 0; }
            .stand-name { font-size: 17px; }
            .stand-amt { font-size: 24px; letter-spacing: -0.015em; }
            .settle-card { padding: 24px; }
        }
    </style>

    <div class="t-head">
        <div class="t-crumb"><a href="{{ route('home') }}" class="wordmark dim">Pokie</a><span class="crumb-sep">/</span></div>
        <div style="min-width: 0; flex: 1;">
            <div class="t-name truncate">{{ $table->name }}</div>
            <div class="t-meta">
                @if($isManager)
                    <span class="pill"><i class="bi bi-key" style="font-size: 11px;" aria-hidden="true"></i>Managing</span>
                    <span class="muted-sm">{{ $metaText }}</span>
                @else
                    <span class="muted-sm">Viewing · {{ $metaText }}</span>
                @endif
            </div>
        </div>
        <button type="button" class="btn btn-gray icon-btn share-btn" aria-label="Share this table"
                data-share-url="{{ route('table.show', ['token' => $table->token]) }}">
            <i class="bi bi-share" aria-hidden="true"></i><span class="share-text">Share</span>
        </button>
    </div>

    @if($isManager && $players->isEmpty())
        {{-- Manager · empty table --}}
        <div class="t-page">
            <div class="banner-info"><span style="font-weight: 600;">Your table is ready.</span> Share the view link with players — keep the manage link to yourself.</div>
            <form method="POST" action="{{ route('table.players.store', ['token' => $table->token, 'manager_token' => $managerToken]) }}" style="display: flex; flex-direction: column; gap: 8px;">
                @csrf
                <label for="add-player" class="eyebrow">Add player</label>
                <div style="display: flex; gap: 8px;">
                    <input id="add-player" type="text" name="name" value="{{ old('name') }}" required placeholder="Name" class="input" style="flex: 1; min-width: 0;"
                           @error('name') aria-invalid="true" aria-describedby="add-player-error" @enderror>
                    <button type="submit" class="btn btn-dark" style="padding: 0 22px;">Add</button>
                </div>
                @error('name')
                    <p id="add-player-error" class="error-text">{{ $message }}</p>
                @enderror
            </form>
            <div class="empty-card card-subtle">
                <div style="font: 500 15px 'Instrument Sans', sans-serif; color: #111827;">No players yet</div>
                <div style="font: 400 14px/1.5 'Instrument Sans', sans-serif; color: #a1a1aa; max-width: 230px; text-wrap: pretty;">Add everyone at the table, then record their first buy-ins.</div>
            </div>
        </div>
    @else
        <div class="t-page {{ $isManager ? 't-grid' : '' }}">
            <div class="t-col">
                @if(session('invalid_manager'))
                    <div class="empty-card card-subtle" style="gap: 10px; padding: 36px 24px;">
                        <div style="font: 600 17px 'Instrument Sans', sans-serif; color: #111827; letter-spacing: -0.01em;">This manage link isn't valid</div>
                        <div style="font: 400 14px/1.55 'Instrument Sans', sans-serif; color: #71717a; max-width: 250px; text-wrap: pretty;">It may have been mistyped or replaced. You can still watch the table, or start a new one.</div>
                        <div style="display: flex; gap: 6px; margin-top: 6px;">
                            <a href="{{ route('table.show', ['token' => $table->token]) }}" class="btn btn-sm btn-white">Open view-only</a>
                            <a href="{{ route('home') }}" class="btn btn-sm btn-dark">New table</a>
                        </div>
                    </div>
                @endif

                @if($allSettled)
                    <div class="banner-success">
                        <div style="font: 600 18px 'Instrument Sans', sans-serif; color: #15803d; letter-spacing: -0.01em;">All settled up 🎉</div>
                        <div style="font: 400 14px 'Instrument Sans', sans-serif; color: #71717a;">No payments needed — everyone is even.</div>
                    </div>
                @endif

                @if($players->isEmpty())
                    <div class="empty-card card-subtle">
                        <div style="font: 500 15px 'Instrument Sans', sans-serif; color: #111827;">No players yet</div>
                        <div style="font: 400 14px/1.5 'Instrument Sans', sans-serif; color: #a1a1aa; max-width: 230px; text-wrap: pretty;">Ask the host to add players and record the first buy-ins.</div>
                    </div>
                @else
                    <div style="display: flex; flex-direction: column;">
                        <div style="display: flex; align-items: baseline; gap: 8px; padding-bottom: 10px;">
                            <span class="eyebrow">Standings</span>
                            @if($players->count() > 6)
                                <span style="font: 400 12px 'Instrument Sans', sans-serif; color: #a1a1aa;">{{ $players->count() }} players</span>
                            @endif
                        </div>
                        @foreach($players as $player)
                            @php $bal = $player->display_amount; @endphp
                            <div class="stand-row rowline">
                                <div class="stand-name truncate">{{ $player->name }}</div>
                                @if(abs($bal) < 0.005)
                                    <div style="display: flex; align-items: baseline; gap: 8px;"><span style="font: 400 12px 'Instrument Sans', sans-serif; color: #a1a1aa;">even</span><span class="stand-amt tabular zero">$0.00</span></div>
                                @else
                                    <div class="stand-amt tabular {{ $bal > 0 ? 'pos' : 'neg' }}">{{ $money($bal, true) }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if($transactions->isNotEmpty())
                        <div class="card-subtle settle-card">
                            <div class="eyebrow" style="padding-bottom: 8px;">To settle up</div>
                            @foreach($transactions as $tx)
                                <div class="settle-row">
                                    <div class="settle-who">{{ $tx->from->name }} pays <b>{{ $tx->to->name }}</b></div>
                                    <div class="settle-amt tabular">{{ $money($tx->amount) }}</div>
                                </div>
                            @endforeach
                            <div style="padding-top: 10px;" class="muted-sm">{{ $transactions->count() }} {{ Str::plural('payment', $transactions->count()) }} and everyone is even.</div>
                        </div>
                    @endif
                @endif

                @if(!$isManager)
                    <p class="muted-sm" style="text-align: center;">Read-only link · ask the host to record money</p>
                @endif
            </div>

            @if($isManager)
                <div class="t-col">
                    @if($players->isNotEmpty())
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <div class="eyebrow">Record money</div>
                            <div role="group" aria-label="Transaction type" class="seg">
                                <button type="button" data-tab="buyin" aria-pressed="{{ $activeTab === 'buyin' ? 'true' : 'false' }}">Buy-in</button>
                                <button type="button" data-tab="payback" aria-pressed="{{ $activeTab === 'payback' ? 'true' : 'false' }}">Payback</button>
                                <button type="button" data-tab="settle" aria-pressed="{{ $activeTab === 'settle' ? 'true' : 'false' }}">Settle</button>
                            </div>

                            @foreach([
                                'buyin' => ['route' => route('table.buy-ins.store', ['token' => $table->token, 'manager_token' => $managerToken]), 'label' => 'Record buy-in'],
                                'payback' => ['route' => route('table.paybacks.store', ['token' => $table->token, 'manager_token' => $managerToken]), 'label' => 'Record payback'],
                                'settle' => ['route' => route('table.settlements.store', ['token' => $table->token, 'manager_token' => $managerToken]), 'label' => 'Record settlement'],
                            ] as $kind => $cfg)
                                @php $isActive = $activeTab === $kind; @endphp
                                <form method="POST" action="{{ $cfg['route'] }}" data-panel="{{ $kind }}" class="{{ $isActive ? '' : 'hidden' }}" style="display: {{ $isActive ? 'flex' : 'none' }}; flex-direction: column; gap: 12px;">
                                    @csrf
                                    <input type="hidden" name="form" value="{{ $kind }}">
                                    <div class="field">
                                        <label for="{{ $kind }}-player" class="label">Player</label>
                                        <select id="{{ $kind }}-player" name="player_id" required class="input">
                                            @foreach($table->players as $p)
                                                <option value="{{ $p->id }}" @if($isActive && (string) old('player_id') === (string) $p->id) selected @endif>{{ $p->name }}</option>
                                            @endforeach
                                        </select>
                                        @if($isActive)
                                            @error('player_id')<p class="error-text">{{ $message }}</p>@enderror
                                        @endif
                                    </div>
                                    <div class="field">
                                        <label for="{{ $kind }}-amount" class="label">Amount</label>
                                        <div class="amount-wrap {{ $isActive && $errors->has('amount') ? 'invalid' : '' }}">
                                            <span class="currency" aria-hidden="true">$</span>
                                            <input id="{{ $kind }}-amount" type="text" inputmode="decimal" name="amount" required
                                                   placeholder="{{ $kind === 'settle' ? '50.00 or -50.00' : '50.00' }}"
                                                   value="{{ $isActive ? old('amount') : '' }}"
                                                   @if($isActive && $errors->has('amount')) aria-invalid="true" aria-describedby="{{ $kind }}-amount-error" @endif
                                                   data-amount>
                                        </div>
                                        @if($isActive)
                                            @error('amount')<p id="{{ $kind }}-amount-error" class="error-text">Enter a dollar amount, like 50 or 42.50.</p>@enderror
                                        @endif
                                        @if($kind === 'settle')
                                            <p class="hint">Positive if the bank pays them, negative if they pay the bank.</p>
                                        @endif
                                    </div>
                                    <button type="submit" class="btn btn-green" data-record data-base-label="{{ $cfg['label'] }}">{{ $cfg['label'] }}</button>
                                </form>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('table.players.store', ['token' => $table->token, 'manager_token' => $managerToken]) }}" style="display: flex; flex-direction: column; gap: 8px;">
                        @csrf
                        <label for="add-player" class="eyebrow">Add player</label>
                        <div style="display: flex; gap: 8px;">
                            <input id="add-player" type="text" name="name" value="{{ old('name') }}" required placeholder="Name" class="input" style="flex: 1; min-width: 0;"
                                   @error('name') aria-invalid="true" aria-describedby="add-player-error" @enderror>
                            <button type="submit" class="btn btn-gray" style="padding: 0 20px;">Add</button>
                        </div>
                        @error('name')
                            <p id="add-player-error" class="error-text">{{ $message }}</p>
                        @enderror
                    </form>

                    <div style="display: flex; flex-direction: column;">
                        <div style="display: flex; align-items: baseline; gap: 8px; padding-bottom: 10px;">
                            <span class="eyebrow">Activity</span>
                            @if(isset($logs) && $logs->count() > 6)
                                <span style="font: 400 12px 'Instrument Sans', sans-serif; color: #a1a1aa;">{{ $logs->count() }} entries</span>
                            @endif
                        </div>
                        @if(!isset($logs) || $logs->isEmpty())
                            <p class="muted-sm rowline" style="padding: 13px 0;">No activity yet — record the first buy-in above.</p>
                        @else
                            @foreach($logs as $log)
                                @php
                                    $meta = [
                                        'buy_in' => ['icon' => 'bi-arrow-down-circle', 'color' => '#16a34a', 'verb' => 'buy-in', 'noun' => 'buy-in', 'route' => route('table.buy-ins.destroy', ['token' => $table->token, 'manager_token' => $managerToken, 'id' => $log->id])],
                                        'payback' => ['icon' => 'bi-arrow-up-circle', 'color' => '#dc2626', 'verb' => 'payback', 'noun' => 'payback', 'route' => route('table.paybacks.destroy', ['token' => $table->token, 'manager_token' => $managerToken, 'id' => $log->id])],
                                        'settlement' => ['icon' => 'bi-cash-coin', 'color' => '#2563eb', 'verb' => 'settled', 'noun' => 'settlement', 'route' => route('table.settlements.destroy', ['token' => $table->token, 'manager_token' => $managerToken, 'id' => $log->id])],
                                    ][$log->type];
                                @endphp
                                <div class="act-item {{ $loop->index >= 6 ? 'act-old hidden' : '' }}">
                                    <div class="act-row rowline">
                                        <i class="bi {{ $meta['icon'] }} act-icon" style="color: {{ $meta['color'] }};" aria-hidden="true"></i>
                                        <div style="flex: 1; min-width: 0;">
                                            <div class="act-label truncate">{{ $log->player_name }} · {{ $meta['verb'] }}</div>
                                            <div class="act-time">{{ $logTime($log->created_at) }}</div>
                                        </div>
                                        <div class="act-amt tabular">{{ $money($log->amount) }}</div>
                                        <button type="button" class="act-del" data-del aria-label="Delete this {{ $meta['noun'] }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                    </div>
                                    <div class="act-confirm hidden">
                                        <div class="act-confirm-text">Delete this {{ $meta['noun'] }}? Standings will update.</div>
                                        <div style="display: flex; gap: 6px;">
                                            <button type="button" class="btn btn-sm btn-white" data-cancel>Cancel</button>
                                            <form method="POST" action="{{ $meta['route'] }}" style="margin: 0;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-red">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            @if($logs->count() > 6)
                                <button type="button" class="btn btn-sm btn-gray" data-show-older style="margin-top: 8px; width: 100%;">Show {{ $logs->count() - 6 }} older {{ Str::plural('entry', $logs->count() - 6) }}</button>
                            @endif
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        // Share: copy the view link
        var shareBtn = document.querySelector('[data-share-url]');
        if (shareBtn) {
            shareBtn.addEventListener('click', function () {
                var url = shareBtn.getAttribute('data-share-url');
                navigator.clipboard.writeText(url).then(function () {
                    var icon = shareBtn.querySelector('i');
                    var text = shareBtn.querySelector('.share-text');
                    icon.className = 'bi bi-check-lg';
                    if (text) text.textContent = 'Copied';
                    setTimeout(function () {
                        icon.className = 'bi bi-share';
                        if (text) text.textContent = 'Share';
                    }, 2000);
                });
            });
        }

        // Record money: segmented control
        document.querySelectorAll('.seg [data-tab]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.seg [data-tab]').forEach(function (b) { b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'); });
                document.querySelectorAll('[data-panel]').forEach(function (panel) {
                    var active = panel.getAttribute('data-panel') === btn.getAttribute('data-tab');
                    panel.classList.toggle('hidden', !active);
                    panel.style.display = active ? 'flex' : 'none';
                });
            });
        });

        // Live amount on the record button, e.g. "Record buy-in · $50.00"
        document.querySelectorAll('[data-panel]').forEach(function (panel) {
            var input = panel.querySelector('[data-amount]');
            var btn = panel.querySelector('[data-record]');
            if (!input || !btn) return;
            input.addEventListener('input', function () {
                var v = parseFloat(input.value);
                var base = btn.getAttribute('data-base-label');
                btn.textContent = (isFinite(v) && v !== 0) ? base + ' · $' + Math.abs(v).toFixed(2) : base;
            });
        });

        // Inline delete confirm in the activity log
        document.querySelectorAll('.act-item').forEach(function (item) {
            var del = item.querySelector('[data-del]');
            var confirmEl = item.querySelector('.act-confirm');
            var cancel = item.querySelector('[data-cancel]');
            if (!del || !confirmEl) return;
            del.addEventListener('click', function () {
                document.querySelectorAll('.act-item.open').forEach(function (other) {
                    if (other !== item) {
                        other.classList.remove('open');
                        other.querySelector('.act-confirm').classList.add('hidden');
                    }
                });
                var opening = confirmEl.classList.contains('hidden');
                confirmEl.classList.toggle('hidden', !opening);
                item.classList.toggle('open', opening);
            });
            cancel.addEventListener('click', function () {
                confirmEl.classList.add('hidden');
                item.classList.remove('open');
            });
        });

        // Reveal older activity entries
        var showOlder = document.querySelector('[data-show-older]');
        if (showOlder) {
            showOlder.addEventListener('click', function () {
                document.querySelectorAll('.act-old').forEach(function (el) { el.classList.remove('hidden'); });
                showOlder.remove();
            });
        }
    </script>
@endsection
