@php
    $globalFailure = collect($errors->get('email'))->first(
        fn (string $message): bool => collect(__('oidc::auth'))->contains(
            fn (string $reason): bool => str_starts_with($message, \Illuminate\Support\Str::before($reason, ':')),
        ),
    );

    if ($globalFailure !== null) {
        $errors->put('default', new \Illuminate\Support\MessageBag(
            collect($errors->getBag('default')->messages())->except('email')->all(),
        ));
    }
@endphp

<div>
    <x-oidc::heading>{{ __('oidc::screens.login.heading') }}</x-oidc::heading>

    @if ($globalFailure !== null)
        <x-oidc::alert tone="error" :title="__('oidc::screens.login.error_title')" class="mt-6">
            {{ $globalFailure }}
        </x-oidc::alert>
    @endif

    <form wire:submit="authenticate" class="mt-8 flex flex-col gap-6">
        <x-oidc::field
            name="email"
            type="email"
            :label="__('oidc::screens.login.email')"
            :placeholder="__('oidc::screens.login.email_placeholder')"
            autocomplete="username"
            required
            autofocus />

        <x-oidc::field
            name="password"
            type="password"
            :label="__('oidc::screens.login.password')"
            :placeholder="__('oidc::screens.login.password_placeholder')"
            autocomplete="current-password"
            required />

        <div class="flex flex-wrap items-center justify-between gap-4">
            <x-oidc::checkbox name="remember" :label="__('oidc::screens.login.remember')" />

            <x-oidc::link :href="route('password.forgot')">{{ __('oidc::screens.login.forgot') }}</x-oidc::link>
        </div>

        <x-oidc::button block>{{ __('oidc::screens.login.submit') }}</x-oidc::button>
    </form>

    <x-oidc::hint class="mt-8">{{ __('oidc::screens.login.support') }}</x-oidc::hint>
</div>
