@props([
    'headline' => null,
    'tagline' => null,
])

<div {{ $attributes->merge(['class' => 'flex items-center justify-between gap-6 bg-ink px-6 py-6 lg:flex-col lg:items-start lg:justify-center lg:gap-6 lg:px-12 lg:py-12']) }}>
    <x-oidc::brand.logo tone="light" :width="200" class="lg:w-[260px]" />

    <div class="max-lg:hidden lg:max-w-[420px]">
        <p class="font-display text-[32px]/10 font-bold text-inverse">{{ $headline ?? __('oidc::screens.brand.headline') }}</p>
        <p class="mt-6 text-sm/5 text-inverse-muted">{{ $tagline ?? __('oidc::screens.brand.tagline') }}</p>
    </div>
</div>
