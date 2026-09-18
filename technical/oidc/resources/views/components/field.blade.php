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
    $revealable = $type === 'password';

    $described = array_filter([
        $hint !== null ? $name.'-hint' : null,
        $invalid ? $name.'-error' : null,
    ]);

    $metrics = match ((int) $size) {
        30 => 'h-[30px] text-xs/4',
        36 => 'h-9 text-sm/5',
        40 => 'h-10 text-sm/5',
        default => 'h-12 text-sm/5',
    };

    $border = $invalid ? 'border-error-line' : 'border-line hover:border-line-strong';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-0.5']) }}>
    <label for="{{ $name }}" class="text-sm/5 font-bold text-title">
        {{ $label }}@if ($required)<span class="text-brand" title="{{ __('oidc::screens.field.required') }}"> *</span>@endif
    </label>

    <div class="relative" @if ($revealable) x-data="{ visible: false }" @endif>
        <input id="{{ $name }}"
               name="{{ $name }}"
               @if ($revealable) x-bind:type="visible ? 'text' : 'password'" type="password" @else type="{{ $type }}" @endif
               wire:model="{{ $property }}"
               @if ($placeholder !== null) placeholder="{{ $placeholder }}" @endif
               @if ($autocomplete !== null) autocomplete="{{ $autocomplete }}" @endif
               @required($required)
               @disabled($disabled)
               @if ($autofocus) autofocus @endif
               @if ($invalid) aria-invalid="true" @endif
               @if ($described !== []) aria-describedby="{{ implode(' ', $described) }}" @endif
               class="w-full rounded-field border bg-surface pl-3 text-body transition-colors placeholder:text-muted focus-visible:border-ink focus-visible:bg-surface-subtle focus-visible:outline-none disabled:cursor-not-allowed disabled:bg-surface-subtle disabled:text-disabled {{ $revealable ? 'pr-11' : 'pr-3' }} {{ $metrics }} {{ $border }}">

        @if ($revealable)
            <button type="button"
                    x-on:click="visible = ! visible"
                    x-bind:aria-label="visible ? '{{ __('oidc::screens.field.hide_password') }}' : '{{ __('oidc::screens.field.show_password') }}'"
                    x-bind:aria-pressed="visible ? 'true' : 'false'"
                    class="absolute inset-y-0 right-0 flex items-center px-3 text-muted transition-colors hover:text-body focus-visible:text-ink focus-visible:outline-none">
                <svg class="size-5" viewBox="0 0 20 20" fill="none" aria-hidden="true" x-show="! visible">
                    <path d="M1.7 10S4.7 4.6 10 4.6 18.3 10 18.3 10s-3 5.4-8.3 5.4S1.7 10 1.7 10Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <circle cx="10" cy="10" r="2.5" stroke="currentColor" stroke-width="1.5"></circle>
                </svg>
                <svg class="size-5" viewBox="0 0 20 20" fill="none" aria-hidden="true" x-show="visible" style="display: none">
                    <path d="M8.2 4.8A7.6 7.6 0 0 1 10 4.6c5.3 0 8.3 5.4 8.3 5.4a14 14 0 0 1-2.4 3.1M5.1 5.9A14 14 0 0 0 1.7 10s3 5.4 8.3 5.4c1.7 0 3.1-.5 4.3-1.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="m2.5 2.5 15 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                </svg>
            </button>
        @endif
    </div>

    @if ($hint !== null)
        <x-oidc::hint id="{{ $name }}-hint" class="mt-1">{{ $hint }}</x-oidc::hint>
    @endif

    @error($name)
        <p id="{{ $name }}-error" role="alert" class="mt-1 text-xs/4 text-error-text">{{ $message }}</p>
    @enderror
</div>
