@props([
    'variant' => 'primary',
    'size' => 48,
    'block' => false,
    'type' => 'submit',
    'href' => null,
])

@php
    $palette = match ($variant) {
        'outline' => 'border border-ink bg-transparent text-ink hover:bg-surface-subtle',
        'neutral' => 'border border-line bg-surface-subtle text-body hover:border-line-strong',
        'ghost' => 'bg-transparent text-body hover:text-title',
        default => 'bg-brand text-inverse hover:bg-brand-hover active:bg-brand-active',
    };

    $metrics = match ((int) $size) {
        30 => 'h-[30px] px-3 text-xs/4',
        36 => 'h-9 px-4 text-sm/5',
        40 => 'h-10 px-5 text-sm/5',
        default => 'h-12 px-6 text-sm/5',
    };

    $shape = 'inline-flex items-center justify-center gap-2 rounded-field font-bold focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink disabled:bg-surface-subtle disabled:text-disabled';

    $classes = implode(' ', array_filter([$shape, $metrics, $palette, $block ? 'w-full' : null]));
@endphp

@if ($href !== null)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
