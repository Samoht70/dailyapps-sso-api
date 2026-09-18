@props(['level' => 1])

@php
    $level = min(3, max(1, (int) $level));

    $typography = match ($level) {
        2 => 'text-xl/7',
        3 => 'text-base/6',
        default => 'text-2xl/8 [[data-shell=app]_&]:text-[32px]/10',
    };
@endphp

<h{{ $level }} {{ $attributes->merge(['class' => 'font-display font-bold text-title '.$typography]) }}>{{ $slot }}</h{{ $level }}>
