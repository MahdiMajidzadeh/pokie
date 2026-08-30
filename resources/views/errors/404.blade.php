@extends('layouts.app')

@section('title', 'Table not found — Pokie')

@section('content')
    <div class="flex min-h-dvh flex-col">
        <div class="flex items-center px-6 pt-5 sm:px-10 sm:pt-6">
            <a href="{{ route('home') }}" class="text-[15px] font-semibold tracking-tight text-zinc-900">Pokie</a>
        </div>
        <div class="flex flex-1 items-center justify-center px-6 py-6">
            <mds:empty-state
                icon="face-frown"
                title="This table doesn't exist"
                description="The link may be mistyped or the table was never created. Start a fresh one — it takes ten seconds."
            >
                <flux:button variant="primary" href="{{ route('home') }}">Create a table</flux:button>
            </mds:empty-state>
        </div>
    </div>
@endsection
