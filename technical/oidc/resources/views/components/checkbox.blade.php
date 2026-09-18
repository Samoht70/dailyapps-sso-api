@props([
    'name',
    'label',
    'model' => null,
])

@php
    $property = $model ?? $name;
@endphp

<label for="{{ $name }}" {{ $attributes->merge(['class' => 'flex min-h-6 cursor-pointer select-none items-center gap-2 text-sm/5 text-body']) }}>
    <input id="{{ $name }}"
           name="{{ $name }}"
           type="checkbox"
           wire:model="{{ $property }}"
           class="peer sr-only">

    <span aria-hidden="true"
          class="relative inline-flex size-5 shrink-0 items-center justify-center rounded-check border-2 border-line transition-colors peer-hover:border-line-strong peer-checked:border-brand peer-checked:bg-brand peer-checked:*:opacity-100 peer-focus-visible:border-ink">
        <svg class="size-3 text-inverse opacity-0 transition-opacity" viewBox="0 0 16 18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="1 9 6 14 15 4"></polyline>
        </svg>
    </span>

    {{ $label }}
</label>
