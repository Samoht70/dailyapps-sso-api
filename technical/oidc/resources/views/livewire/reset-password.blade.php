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

<div>
    @if ($expired)
        <x-oidc::heading>{{ __('oidc::screens.link_expired.reset_heading') }}</x-oidc::heading>

        <x-oidc::alert tone="warning" class="mt-6">
            {{ __('oidc::screens.reset_password.invalid') }}
        </x-oidc::alert>

        <x-oidc::button :href="route('password.forgot')" block class="mt-8">
            {{ __('oidc::screens.link_expired.request_new') }}
        </x-oidc::button>

        <x-oidc::link :href="route('login')" icon="chevron-left" class="mt-8">
            {{ __('oidc::screens.link_expired.back') }}
        </x-oidc::link>
    @else
        <x-oidc::heading>{{ __('oidc::screens.reset_password.heading') }}</x-oidc::heading>

        @if ($refusedToken !== null)
            <x-oidc::alert tone="error" class="mt-6">{{ $refusedToken }}</x-oidc::alert>
        @endif

        <form wire:submit="resetPassword" class="mt-8 flex flex-col gap-6">
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
                :placeholder="__('oidc::screens.reset_password.password_placeholder')"
                :hint="__('oidc::screens.account.password_hint', ['min' => \Functional\Users\Rules\PasswordStrength::MINIMUM_LENGTH])"
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

        <x-oidc::link :href="route('login')" icon="chevron-left" class="mt-8">
            {{ __('oidc::screens.forgot_password.back') }}
        </x-oidc::link>
    @endif
</div>
