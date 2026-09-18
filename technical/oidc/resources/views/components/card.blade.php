@props([
    'heading' => null,
    'hint' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-module bg-surface p-6 shadow-module']) }}>
    @if ($heading !== null)
        <x-oidc::heading :level="2">{{ $heading }}</x-oidc::heading>
    @endif

    @if ($hint !== null)
        <x-oidc::hint @class(['mt-1' => $heading !== null])>{{ $hint }}</x-oidc::hint>
    @endif

    <div @class(['mt-6' => $heading !== null || $hint !== null])>{{ $slot }}</div>
</div>
