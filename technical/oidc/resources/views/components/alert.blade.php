@props([
    'tone' => 'info',
    'title' => null,
])

@php
    $palette = match ($tone) {
        'success' => 'border-success-line bg-success-surface text-success-text',
        'error' => 'border-error-line bg-error-surface text-error-text',
        'warning' => 'border-warning-line bg-warning-surface text-warning-text',
        default => 'border-info-line bg-info-surface text-info-text',
    };

    $role = in_array($tone, ['error', 'warning'], true) ? 'alert' : 'status';
@endphp

<div role="{{ $role }}" {{ $attributes->merge(['class' => 'rounded-field border px-4 py-3 text-sm/5 '.$palette]) }}>
    @if ($title !== null)
        <p class="font-bold">{{ $title }}</p>
    @endif

    <div @class(['mt-1' => $title !== null])>{{ $slot }}</div>
</div>
