@extends('layouts.app')

@section('title', 'Table not found — Pokie')

@section('content')
    <div style="min-height: 100dvh; display: flex; flex-direction: column;">
        <div class="topbar"><a href="{{ route('home') }}" class="wordmark">Pokie</a></div>
        <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 24px;">
            <div style="width: 100%; max-width: 300px; display: flex; flex-direction: column; align-items: center; gap: 12px; text-align: center;">
                <div style="font: 600 22px 'Instrument Sans', sans-serif; color: #111827; letter-spacing: -0.015em;">This table doesn't exist</div>
                <p style="font: 400 14px/1.55 'Instrument Sans', sans-serif; color: #71717a; text-wrap: pretty;">The link may be mistyped or the table was never created. Start a fresh one — it takes ten seconds.</p>
                <a href="{{ route('home') }}" class="btn btn-dark" style="width: 100%; margin-top: 6px;">Create a table</a>
                <a href="{{ route('home') }}" style="font: 500 13px 'Instrument Sans', sans-serif;">Go to the home page</a>
            </div>
        </div>
    </div>
@endsection
