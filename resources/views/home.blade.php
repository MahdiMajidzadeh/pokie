@extends('layouts.app')

@section('title', 'New Table')

@section('content')
    <div class="text-center mb-8">
        <h1 class="display-6 fw-bold mb-3" style="color: #111827;">Simple poker tracking for everyone</h1>
        <p class="text-body-secondary mb-0" style="max-width: 480px; margin-left: auto; margin-right: auto;">
            Create a table, add players, and track buy-ins and settlements. Get a manager link to run your poker night.
        </p>
    </div>

    <div class="card border-0 rounded-3 shadow-sm mb-6" style="background: #ffffff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.06);">
        <div class="card-body p-5 p-lg-6">
            <h2 class="h5 fw-semibold mb-2" style="color: #2563eb;">Create a table</h2>
            <p class="text-body-secondary mb-4 small">Enter a name for your table. You will get a manager link to add players and record buy-ins and paybacks.</p>
            <form action="{{ route('tables.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="name" class="form-label fw-medium small" style="color: #374151;">Table name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="form-control form-control-lg rounded-3"
                           style="border: 1px solid #e5e7eb;"
                           placeholder="e.g. Friday game">
                    @error('name')
                        <p class="mt-1 small text-danger mb-0">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary btn-lg rounded-3 px-5" style="background-color: #2563eb; border-color: #2563eb;">
                    Create table
                </button>
            </form>
        </div>
    </div>

    @if(!empty($recentTables))
        <div class="card border-0 rounded-3 shadow-sm mb-4" style="background: #ffffff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.06);">
            <div class="card-body p-5">
                <h2 class="h6 fw-semibold mb-3" style="color: #374151;">Last opened tables</h2>
                <ul class="list-unstyled mb-0">
                    @foreach($recentTables as $recent)
                        <li class="d-flex align-items-center justify-content-between gap-3 py-3 {{ !$loop->last ? 'border-bottom' : '' }}" style="{{ !$loop->last ? 'border-color: #f3f4f6 !important;' : '' }}">
                            <a href="{{ route('table.show', ['token' => $recent['token'] ?? '']) }}" class="text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-check-circle-fill" style="color: #22c55e; font-size: 1rem;"></i>
                                <span class="fw-medium" style="color: #111827;">{{ $recent['name'] ?? 'Table' }}</span>
                            </a>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <a href="{{ route('table.show', ['token' => $recent['token'] ?? '']) }}" class="btn btn-sm rounded-3" style="background-color: #f3f4f6; color: #374151; border: none;">View</a>
                                @if(!empty($recent['manager_token']))
                                    <a href="{{ route('table.manager', ['token' => $recent['token'] ?? '', 'manager_token' => $recent['manager_token']]) }}" class="btn btn-sm btn-primary rounded-3 px-3" style="background-color: #2563eb; border-color: #2563eb;">
                                        <i class="bi bi-key me-1"></i>Manager
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif
@endsection
