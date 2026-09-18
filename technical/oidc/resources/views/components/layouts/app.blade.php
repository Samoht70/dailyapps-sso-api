@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    {{ Vite::fonts() }}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-shell="app" class="min-h-full overflow-x-hidden bg-page font-sans text-body antialiased">
<header class="border-b border-line bg-surface">
    <div class="mx-auto flex w-full max-w-[720px] flex-wrap items-center justify-between gap-4 px-6 py-4">
        <x-oidc::brand.logo tone="dark" :width="150" />

        <div class="flex items-center gap-4">
            <span class="text-sm/5 text-muted max-sm:hidden">{{ auth()->user()?->email }}</span>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-oidc::button variant="outline" :size="36">{{ __('oidc::screens.account.logout') }}</x-oidc::button>
            </form>
        </div>
    </div>
</header>

<main class="mx-auto w-full max-w-[720px] px-6 py-10">
    {{ $slot }}
</main>
</body>
</html>
