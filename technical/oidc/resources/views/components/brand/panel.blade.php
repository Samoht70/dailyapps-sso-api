@props([
    'headline' => null,
    'tagline' => null,
])

<div {{ $attributes->merge(['class' => 'flex items-center justify-between gap-8 bg-ink px-6 py-6 lg:flex-col lg:items-start lg:justify-between lg:px-12 lg:py-16']) }}>
    <x-oidc::brand.logo tone="light" :width="160" class="lg:w-[225px]" />

    <div class="max-lg:hidden">
        <p class="font-display text-[32px]/10 font-bold text-inverse">{{ $headline ?? __('oidc::screens.brand.headline') }}</p>
        <p class="mt-4 text-sm/5 text-inverse-muted">{{ $tagline ?? __('oidc::screens.brand.tagline') }}</p>
    </div>
</div>
