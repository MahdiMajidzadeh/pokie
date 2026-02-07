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
@endsection
