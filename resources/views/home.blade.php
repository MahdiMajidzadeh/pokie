@extends('layouts.app')

@section('title', 'Pokie — Track the money at poker night')

@section('content')
    <style>
        .home-page { max-width: 375px; margin: 0 auto; padding: 48px 24px 64px; display: flex; flex-direction: column; gap: 32px; }
        .home-hero { display: flex; flex-direction: column; gap: 10px; }
        .home-hero h1 { font: 600 30px/1.15 'Instrument Sans', sans-serif; color: #111827; letter-spacing: -0.02em; text-wrap: pretty; }
        .home-hero p { font: 400 15px/1.55 'Instrument Sans', sans-serif; color: #71717a; text-wrap: pretty; }
        .home-form { display: flex; flex-direction: column; gap: 10px; margin: 0; }
        .home-form-row { display: flex; flex-direction: column; gap: 10px; }
        .recent-row { display: flex; align-items: center; gap: 12px; padding: 14px 0; }
        @media (min-width: 640px) {
            .home-page { max-width: 608px; padding: 88px 24px 104px; gap: 40px; }
            .home-hero { text-align: center; gap: 12px; }
            .home-hero h1 { font-size: 42px; line-height: 1.1; letter-spacing: -0.025em; }
            .home-hero p { font-size: 17px; }
            .home-form-row { flex-direction: row; }
            .home-form-row .input { flex: 1; }
        }
    </style>

    <div class="topbar"><a href="{{ route('home') }}" class="wordmark">Pokie</a></div>

    <div class="home-page">
        <div class="home-hero">
            <h1>Track the money at poker night.</h1>
            <p>No signup. Make a table, share the link, settle up at the end.</p>
        </div>

        <form class="home-form" action="{{ route('tables.store') }}" method="POST" data-create-table>
            @csrf
            <label for="name" class="label">Table name</label>
            <div class="home-form-row">
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       class="input" placeholder="Friday night at Sam's"
                       @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                <button type="submit" class="btn btn-dark" data-submit>Create table</button>
            </div>
            @error('name')
                <p id="name-error" class="error-text">Enter a table name to continue.</p>
            @enderror
            <p class="hint" style="margin-top: 2px;">You'll get a manage link for you and a view link for players.</p>
        </form>

        @if(!empty($recentTables))
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <div class="eyebrow" style="padding-bottom: 8px;">Recent tables</div>
                @foreach($recentTables as $recent)
                    @php
                        $recentDate = null;
                        if (!empty($recent['at'])) {
                            $at = \Illuminate\Support\Carbon::parse($recent['at'])->timezone('Asia/Tehran');
                            $recentDate = $at->isToday() ? 'Today' : $at->format('M j');
                        }
                    @endphp
                    <div class="recent-row rowline">
                        <div style="min-width: 0; flex: 1;">
                            <div class="truncate" style="font: 500 15px 'Instrument Sans', sans-serif; color: #111827;">{{ $recent['name'] ?? 'Table' }}</div>
                            @if($recentDate)
                                <div class="muted-sm">{{ $recentDate }}</div>
                            @endif
                        </div>
                        <div style="display: flex; gap: 6px;">
                            <a href="{{ route('table.show', ['token' => $recent['token'] ?? '']) }}" class="btn btn-sm btn-gray" aria-label="Open {{ $recent['name'] ?? 'table' }} as viewer">View</a>
                            @if(!empty($recent['manager_token']))
                                <a href="{{ route('table.manager', ['token' => $recent['token'] ?? '', 'manager_token' => $recent['manager_token']]) }}" class="btn btn-sm btn-blue-soft" aria-label="Manage {{ $recent['name'] ?? 'table' }}">Manage</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        document.querySelector('[data-create-table]').addEventListener('submit', function () {
            var btn = this.querySelector('[data-submit]');
            var input = this.querySelector('input[name="name"]');
            if (input.value.trim() !== '') {
                btn.disabled = true;
                btn.textContent = 'Creating…';
                input.readOnly = true;
            }
        });
    </script>
@endsection
