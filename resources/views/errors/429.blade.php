@extends('layouts.app')

@section('title', 'Too many attempts — Pokie')

@section('content')
    <div style="min-height: 100dvh; display: flex; flex-direction: column;">
        <div class="topbar"><a href="{{ route('home') }}" class="wordmark">Pokie</a></div>
        <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 24px;">
            <div style="width: 100%; max-width: 300px; display: flex; flex-direction: column; gap: 16px;">
                <div style="font: 600 22px 'Instrument Sans', sans-serif; color: #111827; letter-spacing: -0.015em;">Superadmin</div>
                <div role="alert" class="alert-error">Too many attempts. Try again in a minute.</div>
                <a href="{{ route('home') }}" style="font: 500 13px 'Instrument Sans', sans-serif; text-align: center;">Back to Pokie</a>
            </div>
        </div>
    </div>
@endsection
