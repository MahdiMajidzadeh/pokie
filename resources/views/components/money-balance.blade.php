{{--
    Signed balance display: green when up, red when down, gray when even.
    mds:price hardcodes its amount color with no override prop, so this
    renders the same shape (bold tabular number + small currency label)
    with the sign color applied.
--}}
@props(['amount', 'size' => null])
@php
    $isZero = abs($amount) < 0.005;
    $isPositive = $amount > 0;
    $colorClass = $isZero
        ? 'text-zinc-400'
        : ($isPositive ? 'text-emerald-600' : 'text-red-600');
    $sizeClass = match ($size) {
        'sm' => 'text-sm',
        'lg' => 'text-[22px] tracking-tight',
        default => 'text-base',
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-baseline gap-1.5 $colorClass"]) }}>
    <span class="{{ $sizeClass }} font-semibold tabular-nums">@if(! $isZero){{ $isPositive ? '+' : '−' }}@endif{{ number_format(abs($amount), 2) }}</span>
    <span class="text-xs opacity-70">{{ config('mds.currency', 'Toman') }}</span>
</span>
