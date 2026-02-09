@extends('layouts.app')

@section('title', 'Superadmin – All Tables')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('home') }}" class="text-body-secondary text-decoration-none small fw-medium">← Home</a>
            <span class="text-body-tertiary">·</span>
            <h1 class="h4 fw-bold mb-0">All tables</h1>
        </div>
        <form action="{{ route('superadmin.logout') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm rounded-3">Log out</button>
        </form>
    </div>

    <div class="card border-0 rounded-3 shadow-sm mb-4">
        <div class="card-body p-0">
            @if($tables->isEmpty())
                <p class="text-body-secondary mb-0 p-4">No tables yet.</p>
            @else
                <ul class="list-group list-group-flush">
                    @foreach($tables as $table)
                        <li class="list-group-item border-0 border-bottom d-flex align-items-center justify-content-between gap-3 py-3 px-4">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-currency-dollar text-primary"></i>
                                <div>
                                    <span class="fw-semibold text-body-emphasis">{{ $table->name }}</span>
                                    <span class="text-body-secondary small d-block">{{ $table->created_at->timezone('Asia/Tehran')->format('M j, Y H:i') }}</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <a href="{{ route('table.show', ['token' => $table->token]) }}" class="btn btn-sm btn-light rounded-3">View</a>
                                <a href="{{ route('table.manager', ['token' => $table->token, 'manager_token' => $table->manager_token]) }}" class="btn btn-sm btn-primary rounded-3">
                                    <i class="bi bi-key me-1"></i>Manager
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if($tables->hasPages())
                    <div class="p-4 border-top">
                        {{ $tables->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
