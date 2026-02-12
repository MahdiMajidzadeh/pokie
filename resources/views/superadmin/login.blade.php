@extends('layouts.app')

@section('title', 'Superadmin Login')

@section('content')
    <div class="text-center mb-6">
        <h1 class="display-6 fw-bold mb-3" style="color: #111827;">Superadmin</h1>
        <p class="text-body-secondary mb-0">Enter the superadmin password to view all tables.</p>
    </div>

    <div class="card border-0 rounded-3 shadow-sm mx-auto" style="max-width: 400px; background: #ffffff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.06);">
        <div class="card-body p-5">
            @if (!$passwordConfigured)
                <p class="text-body-secondary mb-0 small">Set <code>SUPERADMIN_PASSWORD</code> in your <code>.env</code> file to enable superadmin access.</p>
            @else
                <form action="{{ route('superadmin.login') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="password" class="form-label fw-medium small" style="color: #374151;">Password</label>
                        <input type="password" name="password" id="password" required autofocus
                               class="form-control form-control-lg rounded-3"
                               style="border: 1px solid #e5e7eb;"
                               placeholder="Password">
                        @error('password')
                            <p class="mt-1 small text-danger mb-0">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg rounded-3 px-5 w-100" style="background-color: #2563eb; border-color: #2563eb;">
                        Log in
                    </button>
                </form>
            @endif
        </div>
    </div>
    <p class="text-center mt-4">
        <a href="{{ route('home') }}" class="text-body-secondary small text-decoration-none">← Home</a>
    </p>
@endsection
