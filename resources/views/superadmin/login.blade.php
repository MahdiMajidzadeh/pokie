@extends('layouts.app')

@section('title', 'Superadmin Login')

@section('content')
    <div class="card border-0 rounded-3 shadow-sm mb-4">
        <div class="card-body p-4 p-lg-5">
            <h1 class="h4 fw-bold mb-2">Superadmin</h1>
            @if (!$passwordConfigured)
                <p class="text-body-secondary mb-4">Set <code>SUPERADMIN_PASSWORD</code> in your <code>.env</code> file to enable superadmin access.</p>
            @else
                <p class="text-body-secondary mb-4">Enter the superadmin password to view all tables.</p>
                <form action="{{ route('superadmin.login') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="password" class="form-label fw-medium">Password</label>
                        <input type="password" name="password" id="password" required autofocus
                               class="form-control form-control-lg rounded-3 border border-secondary"
                               placeholder="Password">
                        @error('password')
                            <p class="mt-1 small text-danger mb-0">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg rounded-3 px-4">
                        Log in
                    </button>
                </form>
            @endif
        </div>
    </div>
    <p class="text-center">
        <a href="{{ route('home') }}" class="text-body-secondary small text-decoration-none">← Home</a>
    </p>
@endsection
