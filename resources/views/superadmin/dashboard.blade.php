@extends('layouts.app')

@section('title', 'Superadmin – All Tables')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-6">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('home') }}" class="text-body-secondary text-decoration-none small fw-medium">← Home</a>
            <span class="text-body-tertiary">·</span>
            <h1 class="h4 fw-bold mb-0" style="color: #111827;">All tables</h1>
        </div>
        <form action="{{ route('superadmin.logout') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm rounded-3" style="background-color: #f3f4f6; color: #374151; border: none;">Log out</button>
        </form>
    </div>

    <div class="card border-0 rounded-3 shadow-sm mb-4" style="background: #ffffff; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.06);">
        <div class="card-body p-0">
            @if($tables->isEmpty())
                <p class="text-body-secondary mb-0 p-5">No tables yet.</p>
            @else
                <ul class="list-unstyled mb-0">
                    @foreach($tables as $table)
                        <li class="d-flex align-items-center justify-content-between gap-3 py-4 px-5 {{ !$loop->last ? 'border-bottom' : '' }}" style="{{ !$loop->last ? 'border-color: #f3f4f6 !important;' : '' }}">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-check-circle-fill" style="color: #22c55e; font-size: 1.25rem;"></i>
                                <div>
                                    <span class="fw-semibold d-block" style="color: #111827;">{{ $table->name }}</span>
                                    <span class="text-body-secondary small">{{ $table->created_at->timezone('Asia/Tehran')->format('M j, Y H:i') }}</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <a href="{{ route('table.show', ['token' => $table->token]) }}" class="btn btn-sm rounded-3" style="background-color: #f3f4f6; color: #374151; border: none;">View</a>
                                <a href="{{ route('table.manager', ['token' => $table->token, 'manager_token' => $table->manager_token]) }}" class="btn btn-sm btn-primary rounded-3 px-3" style="background-color: #2563eb; border-color: #2563eb;">
                                    <i class="bi bi-key me-1"></i>Manager
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if($tables->hasPages())
                    <div class="p-4 border-top" style="border-color: #f3f4f6 !important;">
                        {{ $tables->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
