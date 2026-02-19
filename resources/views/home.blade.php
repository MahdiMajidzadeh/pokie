@extends('layouts.app')

@section('title', 'New Table')

@section('content')
    <div class="card border-0 rounded-3 shadow-sm mb-4">
        <div class="card-body p-4 p-lg-5">
            <h1 class="h4 fw-bold mb-2">Create a poker table</h1>
            <p class="text-body-secondary mb-4">Enter a name for your table. You will get a manager link to add players and record buy-ins and paybacks.</p>
            <form action="{{ route('tables.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="name" class="form-label fw-medium">Table name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="form-control form-control-lg rounded-3 border border-secondary"
                           placeholder="e.g. Friday game">
                    @error('name')
                        <p class="mt-1 small text-danger mb-0">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary btn-lg rounded-3 px-4">
                    Create table
                </button>
            </form>
        </div>
    </div>

    @if(!empty($recentTables))
        <div class="card border-0 rounded-3 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pt-0 pb-1 px-4 pt-4">
                <h2 class="h6 fw-bold text-body-secondary text-uppercase small mb-0">Last opened tables</h2>
            </div>
            <div class="card-body py-0 px-0">
                <ul class="list-group list-group-flush">
                    @foreach($recentTables as $recent)
                        <li class="list-group-item border-0 border-bottom d-flex align-items-center justify-content-between gap-2 py-3 px-4">
                            <a href="{{ route('table.show', ['token' => $recent['token'] ?? '']) }}" class="text-body-emphasis fw-semibold text-decoration-none d-flex align-items-center gap-2">
                                <i class="bi bi-currency-dollar text-primary"></i>
                                {{ $recent['name'] ?? 'Table' }}
                            </a>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <a href="{{ route('table.show', ['token' => $recent['token'] ?? '']) }}" class="btn btn-sm btn-light rounded-3">View</a>
                                @if(!empty($recent['manager_token']))
                                    <a href="{{ route('table.manager', ['token' => $recent['token'] ?? '', 'manager_token' => $recent['manager_token']]) }}" class="btn btn-sm btn-primary rounded-3">
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
