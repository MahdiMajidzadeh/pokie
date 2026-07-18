@extends('layouts.app')

@section('title', 'Superadmin — Pokie')

@section('inline-errors', '1')

@section('content')
    <div style="min-height: 100dvh; display: flex; flex-direction: column;">
        <div class="topbar"><a href="{{ route('home') }}" class="wordmark">Pokie</a></div>
        <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 24px;">
            @if (!$passwordConfigured)
                <div style="width: 100%; max-width: 380px; display: flex; flex-direction: column; gap: 10px; text-align: center; align-items: center;">
                    <div style="font: 600 22px 'Instrument Sans', sans-serif; color: #111827; letter-spacing: -0.015em;">Superadmin isn't set up</div>
                    <p style="font: 400 14px/1.55 'Instrument Sans', sans-serif; color: #71717a; text-wrap: pretty;">This install has no superadmin password configured, so this area is off. Set <span style="font-family: ui-monospace, monospace; font-size: 13px; background: #f4f4f5; border-radius: 6px; padding: 1px 6px;">SUPERADMIN_PASSWORD</span> to enable it.</p>
                    <a href="{{ route('home') }}" style="font: 500 14px 'Instrument Sans', sans-serif; margin-top: 4px;">Back to Pokie</a>
                </div>
            @else
                <form action="{{ route('superadmin.login') }}" method="POST" style="width: 100%; max-width: 300px; display: flex; flex-direction: column; gap: 16px;">
                    @csrf
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <div style="font: 600 22px 'Instrument Sans', sans-serif; color: #111827; letter-spacing: -0.015em;">Superadmin</div>
                        <p style="font: 400 14px/1.5 'Instrument Sans', sans-serif; color: #71717a;">All tables, one list. Hosts don't need this.</p>
                    </div>
                    @if(session('error') || $errors->has('password'))
                        <div role="alert" class="alert-error">{{ session('error') ?: $errors->first('password') }}</div>
                    @endif
                    <div class="field">
                        <label for="password" class="label">Password</label>
                        <input type="password" name="password" id="password" required autofocus class="input"
                               @if(session('error') || $errors->has('password')) aria-invalid="true" @endif>
                    </div>
                    <button type="submit" class="btn btn-dark">Sign in</button>
                    <a href="{{ route('home') }}" style="font: 500 13px 'Instrument Sans', sans-serif; text-align: center;">Back to Pokie</a>
                </form>
            @endif
        </div>
    </div>
@endsection
