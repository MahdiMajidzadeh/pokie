{{--
    Signed/unsigned money display. mds:price hardcodes its amount color with
    no override prop (confirmed in vendor/mahdimajidzadeh/ds), so a signed
    balance (winner green / loser red / even gray) needs this instead.
    requirement.md design rule: never rely on color alone — the +/− sign
    carries the meaning too. Amounts are always whole numbers (unsigned
    integers, no floats — requirement.md §5.2), so this never shows decimals.
--}}
@props([
    'amount',
    'signed' => false,
    'size' => null,
])
@php
    $amount = (int) $amount;
    $isZero = $amount === 0;
    $isPositive = $amount > 0;

    $colorClass = match (true) {
        ! $signed => 'text-zinc-900',
        $isZero => 'text-zinc-400',
        $isPositive => 'text-emerald-600',
        default => 'text-red-600',
    };

    $sizeClass = match ($size) {
        'sm' => 'text-sm',
        'lg' => 'text-2xl tracking-tight',
        default => 'text-base',
    };

    // U+2212 MINUS SIGN, not a hyphen — reads unambiguously next to digits.
    $sign = $signed && ! $isZero ? ($isPositive ? '+' : "\u{2212}") : '';
@endphp
<span {{ $attributes->merge(['class' => "font-semibold tabular-nums $sizeClass $colorClass"]) }}>{{ $sign }}{{ number_format(abs($amount)) }}</span>
