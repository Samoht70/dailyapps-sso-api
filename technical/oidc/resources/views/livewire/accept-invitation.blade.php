<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-1">
        <x-oidc::heading>{{ __('oidc::screens.invitation.heading') }}</x-oidc::heading>
        <p class="text-sm/5 text-muted">{{ $email }}</p>
    </div>

    <form wire:submit="activate" class="flex flex-col gap-4">
        <x-oidc::field
            name="password"
            type="password"
            :label="__('oidc::screens.invitation.password')"
            :placeholder="__('oidc::screens.invitation.password_placeholder', ['min' => \Functional\Users\Rules\PasswordStrength::MINIMUM_LENGTH])"
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

    <x-oidc::hint class="text-center">{{ __('oidc::screens.invitation.expired_hint') }}</x-oidc::hint>
</div>
