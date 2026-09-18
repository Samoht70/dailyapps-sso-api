@props([
    'name',
    'label',
    'model' => null,
])

@php
    $property = $model ?? $name;
@endphp

<label for="{{ $name }}" {{ $attributes->merge(['class' => 'flex min-h-6 cursor-pointer items-center gap-2 text-sm/5 text-body']) }}>
    <input id="{{ $name }}"
           name="{{ $name }}"
           type="checkbox"
           wire:model="{{ $property }}"
           class="size-5 shrink-0 rounded-check border border-line accent-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus">
    {{ $label }}
</label>
