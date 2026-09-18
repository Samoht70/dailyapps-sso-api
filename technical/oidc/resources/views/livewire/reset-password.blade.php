@php
    $refusedToken = collect($errors->get('email'))->first(
        fn (string $message): bool => $message === __('oidc::screens.reset_password.invalid'),
    );

    if ($refusedToken !== null) {
        $errors->put('default', new \Illuminate\Support\MessageBag(
            collect($errors->getBag('default')->messages())->except('email')->all(),
        ));
    }
@endphp

<div class="flex flex-col gap-6">
    @if ($expired)
        <div class="flex flex-col gap-4">
            <x-oidc::clock />
            <x-oidc::heading>{{ __('oidc::screens.link_expired.reset_heading') }}</x-oidc::heading>
        </div>

        <x-oidc::alert tone="warning" :title="__('oidc::screens.link_expired.alert_title')">
            {{ __('oidc::screens.reset_password.invalid') }}
        </x-oidc::alert>

        <x-oidc::button :href="route('password.forgot')" block>
            {{ __('oidc::screens.link_expired.request_new') }}
        </x-oidc::button>

        <x-oidc::link :href="route('login')" icon="chevron-left" class="self-start">
            {{ __('oidc::screens.link_expired.back') }}
        </x-oidc::link>
    @else
        <x-oidc::heading>{{ __('oidc::screens.reset_password.heading') }}</x-oidc::heading>

        @if ($refusedToken !== null)
            <x-oidc::alert tone="error">{{ $refusedToken }}</x-oidc::alert>
        @endif

        <form wire:submit="resetPassword" class="flex flex-col gap-4">
            <x-oidc::field
                name="email"
                type="email"
                :label="__('oidc::screens.reset_password.email')"
                :placeholder="__('oidc::screens.login.email_placeholder')"
                autocomplete="username"
                required />

            <x-oidc::field
                name="password"
                type="password"
                :label="__('oidc::screens.reset_password.password')"
                :placeholder="__('oidc::screens.reset_password.password_placeholder', ['min' => \Functional\Users\Rules\PasswordStrength::MINIMUM_LENGTH])"
                autocomplete="new-password"
                required
                autofocus />

            <x-oidc::field
                name="password_confirmation"
                type="password"
                :label="__('oidc::screens.reset_password.confirmation')"
                :placeholder="__('oidc::screens.reset_password.confirmation_placeholder')"
                autocomplete="new-password"
                required />

            <x-oidc::button block>{{ __('oidc::screens.reset_password.submit') }}</x-oidc::button>
        </form>

        <x-oidc::hint>{{ __('oidc::screens.reset_password.sessions_hint') }}</x-oidc::hint>
    @endif
</div>
