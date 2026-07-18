@extends('layouts.app')

@section('title', 'Superadmin — All tables')

@section('content')
    <style>
        .sa-page { max-width: 968px; margin: 0 auto; padding: 24px 24px 56px; display: flex; flex-direction: column; gap: 16px; }
        .sa-title { display: flex; align-items: baseline; gap: 8px; }
        .sa-title h1 { font: 600 22px 'Instrument Sans', sans-serif; color: #111827; letter-spacing: -0.02em; }
        .sa-count { font: 400 13px 'Instrument Sans', sans-serif; color: #a1a1aa; font-variant-numeric: tabular-nums; }
        .sa-head-row { display: none; }
        .sa-row { display: flex; align-items: center; gap: 10px; padding: 12px 0; }
        .sa-name { font: 500 15px 'Instrument Sans', sans-serif; color: #111827; }
        .sa-meta { font: 400 12px 'Instrument Sans', sans-serif; color: #a1a1aa; font-variant-numeric: tabular-nums; }
        .sa-cell { display: none; font: 400 14px 'Instrument Sans', sans-serif; color: #71717a; font-variant-numeric: tabular-nums; }
        .sa-pager { display: flex; align-items: center; gap: 8px; padding: 14px 0 0; }
        .page-nav { width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; border: none; border-radius: 12px; background: #f4f4f5; color: #111827; cursor: pointer; }
        .page-nav:hover { background: #e4e4e7; color: #111827; }
        .page-nav.off { background: #fafafa; color: #d4d4d8; cursor: default; pointer-events: none; }
        @media (min-width: 720px) {
            .sa-page { padding: 36px 24px 56px; gap: 20px; }
            .sa-title h1 { font-size: 26px; }
            .sa-count { font-size: 14px; }
            .sa-head-row { display: grid; grid-template-columns: 1fr 130px 100px 100px 88px; gap: 12px; align-items: center; padding: 0 0 10px; }
            .sa-row { display: grid; grid-template-columns: 1fr 130px 100px 100px 88px; gap: 12px; padding: 8px 0; }
            .sa-meta { display: none; }
            .sa-cell { display: block; }
            .sa-cell.num { text-align: right; }
        }
    </style>

    <div class="topbar">
        <a href="{{ route('home') }}" class="wordmark dim">Pokie</a>
        <span class="crumb-sep">/</span>
        <span class="wordmark">Superadmin</span>
        <div style="flex: 1;"></div>
        <form action="{{ route('superadmin.logout') }}" method="POST" style="margin: 0;">
            @csrf
            <button type="submit" class="btn btn-sm btn-gray">Log out</button>
        </form>
    </div>

    <div class="sa-page">
        @if($tables->total() === 0)
            <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 100px 24px;">
                <div style="display: flex; flex-direction: column; align-items: center; gap: 6px; text-align: center;">
                    <div style="font: 500 16px 'Instrument Sans', sans-serif; color: #111827;">No tables yet</div>
                    <div style="font: 400 14px 'Instrument Sans', sans-serif; color: #a1a1aa;">Tables will appear here as soon as someone creates one.</div>
                </div>
            </div>
        @else
            <div class="sa-title">
                <h1>All tables</h1>
                <span class="sa-count">{{ $tables->total() }}</span>
            </div>
            <div style="display: flex; flex-direction: column;">
                <div class="sa-head-row">
                    <div class="eyebrow">Table</div>
                    <div class="eyebrow">Created</div>
                    <div class="eyebrow" style="text-align: right;">Players</div>
                    <div class="eyebrow" style="text-align: right;">Entries</div>
                    <div></div>
                </div>
                @foreach($tables as $table)
                    @php
                        $entries = ($table->buy_ins_count ?? 0) + ($table->paybacks_count ?? 0) + ($table->settlements_count ?? 0);
                        $created = $table->created_at->timezone('Asia/Tehran')->format('M j, Y');
                    @endphp
                    <div class="sa-row rowline">
                        <div style="min-width: 0;">
                            <div class="sa-name truncate">{{ $table->name }}</div>
                            <div class="sa-meta">{{ $created }} · {{ $table->players_count }} {{ Str::plural('player', $table->players_count) }} · {{ $entries }} {{ Str::plural('entry', $entries) }}</div>
                        </div>
                        <div class="sa-cell">{{ $created }}</div>
                        <div class="sa-cell num">{{ $table->players_count }}</div>
                        <div class="sa-cell num">{{ $entries }}</div>
                        <a href="{{ route('table.show', ['token' => $table->token]) }}" class="btn btn-xs btn-gray" style="justify-content: center;" aria-label="Open {{ $table->name }}">Open</a>
                    </div>
                @endforeach
                <div class="sa-pager rowline">
                    <div style="flex: 1;" class="muted-sm tabular">{{ $tables->firstItem() }}–{{ $tables->lastItem() }} of {{ $tables->total() }}</div>
                    @if($tables->previousPageUrl())
                        <a href="{{ $tables->previousPageUrl() }}" class="page-nav" aria-label="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
                    @else
                        <span class="page-nav off" aria-hidden="true"><i class="bi bi-chevron-left"></i></span>
                    @endif
                    @if($tables->nextPageUrl())
                        <a href="{{ $tables->nextPageUrl() }}" class="page-nav" aria-label="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                    @else
                        <span class="page-nav off" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
