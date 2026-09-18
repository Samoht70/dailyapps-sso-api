@props([
    'href',
    'icon' => null,
    'navigate' => true,
])

<a href="{{ $href }}"
   @if ($navigate) wire:navigate @endif
   {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-sm/5 text-focus underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink']) }}>
    @if ($icon === 'chevron-left')
        <svg class="size-4" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <path d="M10 12L6 8L10 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
    @endif

    {{ $slot }}
</a>
