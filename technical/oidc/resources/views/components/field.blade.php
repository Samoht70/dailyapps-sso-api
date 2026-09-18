@props([
    'name',
    'label',
    'type' => 'text',
    'model' => null,
    'size' => 48,
    'placeholder' => null,
    'hint' => null,
    'autocomplete' => null,
    'required' => false,
    'autofocus' => false,
    'disabled' => false,
])

@php
    $property = $model ?? $name;
    $invalid = $errors->has($name);

    $described = array_filter([
        $hint !== null ? $name.'-hint' : null,
        $invalid ? $name.'-error' : null,
    ]);

    $metrics = match ((int) $size) {
        30 => 'h-[30px] px-3 text-xs/4',
        36 => 'h-9 px-3 text-sm/5',
        40 => 'h-10 px-4 text-sm/5',
        default => 'h-12 px-4 text-sm/5',
    };

    $border = $invalid ? 'border-error-line' : 'border-line hover:border-line-strong';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-2']) }}>
    <label for="{{ $name }}" class="text-sm/5 font-bold text-title">{{ $label }}</label>

    <input id="{{ $name }}"
           name="{{ $name }}"
           type="{{ $type }}"
           wire:model="{{ $property }}"
           @if ($placeholder !== null) placeholder="{{ $placeholder }}" @endif
           @if ($autocomplete !== null) autocomplete="{{ $autocomplete }}" @endif
           @required($required)
           @disabled($disabled)
           @if ($autofocus) autofocus @endif
           @if ($invalid) aria-invalid="true" @endif
           @if ($described !== []) aria-describedby="{{ implode(' ', $described) }}" @endif
           class="w-full rounded-field border bg-surface text-body placeholder:text-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus disabled:bg-surface-subtle disabled:text-disabled {{ $metrics }} {{ $border }}">

    @if ($hint !== null)
        <x-oidc::hint id="{{ $name }}-hint">{{ $hint }}</x-oidc::hint>
    @endif

    @error($name)
        <p id="{{ $name }}-error" role="alert" class="text-xs/4 text-error-text">{{ $message }}</p>
    @enderror
</div>
