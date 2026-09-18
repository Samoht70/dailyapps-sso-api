@props([
    'title' => null,
    'headline' => null,
    'tagline' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full overflow-x-hidden bg-page font-sans text-body antialiased">
<div class="flex min-h-dvh flex-col lg:flex-row">
    <x-oidc::brand.panel :headline="$headline" :tagline="$tagline" class="lg:w-[480px] lg:shrink-0" />

    <main class="flex flex-1 items-center justify-center px-6 py-12">
        <div class="w-full max-w-[400px]">
            {{ $slot }}
        </div>
    </main>
</div>
</body>
</html>
