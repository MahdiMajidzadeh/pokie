@extends('layouts.app')

@section('title', 'New Table')

@section('content')
    <h1 class="text-2xl font-bold mb-6">Create a poker table</h1>
    <p class="mb-4 text-stone-600">Enter a name for your table. You will get a manager link to add players and record buy-ins and paybacks.</p>
    <form action="{{ route('tables.store') }}" method="POST" class="space-y-4">
        @csrf
        <div>
            <label for="name" class="block text-sm font-medium mb-1">Table name</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                   class="w-full rounded border border-stone-300 px-3 py-2 focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                   placeholder="e.g. Friday game">
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700">
            Create table
        </button>
    </form>
@endsection
