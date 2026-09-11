@props(['href' => null])
<a href="{{ $href ?? route('home') }}" wire:navigate
   {{ $attributes->merge(['class' => 'text-lg font-bold tracking-tight text-zinc-900']) }}>
    pTable
</a>
