<div>
    <x-oidc::heading>{{ __('oidc::screens.invitation.heading') }}</x-oidc::heading>

    <p class="mt-2 text-sm/5 text-muted">{{ $email }}</p>

    <form wire:submit="activate" class="mt-8 flex flex-col gap-6">
        <x-oidc::field
            name="password"
            type="password"
            :label="__('oidc::screens.invitation.password')"
            :placeholder="__('oidc::screens.invitation.password_placeholder')"
            :hint="__('oidc::screens.account.password_hint', ['min' => \Functional\Users\Rules\PasswordStrength::MINIMUM_LENGTH])"
            autocomplete="new-password"
            required
            autofocus />

        <x-oidc::field
            name="password_confirmation"
            type="password"
            :label="__('oidc::screens.invitation.confirmation')"
            :placeholder="__('oidc::screens.invitation.confirmation_placeholder')"
            autocomplete="new-password"
            required />

        <x-oidc::button block>{{ __('oidc::screens.invitation.submit') }}</x-oidc::button>
    </form>

    <x-oidc::hint class="mt-8">{{ __('oidc::screens.invitation.expired_hint') }}</x-oidc::hint>
</div>
