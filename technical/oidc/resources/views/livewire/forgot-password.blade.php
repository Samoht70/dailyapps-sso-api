<div class="flex flex-col gap-6">
    @if ($sent)
        <x-oidc::heading>{{ __('oidc::screens.forgot_password.heading') }}</x-oidc::heading>

        <x-oidc::alert tone="success" :title="__('oidc::screens.forgot_password.sent_title')">
            {{ __('oidc::screens.forgot_password.sent') }}
        </x-oidc::alert>

        <x-oidc::hint>
            {{ __('oidc::screens.forgot_password.expiry_hint', ['minutes' => config('auth.passwords.users.expire')]) }}
        </x-oidc::hint>
    @else
        <div class="flex flex-col gap-2">
            <x-oidc::heading>{{ __('oidc::screens.forgot_password.heading') }}</x-oidc::heading>
            <p class="text-sm/5 text-muted">{{ __('oidc::screens.forgot_password.intro') }}</p>
        </div>

        <form wire:submit="sendLink" class="flex flex-col gap-4">
            <x-oidc::field
                name="email"
                type="email"
                :label="__('oidc::screens.forgot_password.email')"
                :placeholder="__('oidc::screens.login.email_placeholder')"
                autocomplete="username"
                required
                autofocus />

            <x-oidc::button block>{{ __('oidc::screens.forgot_password.submit') }}</x-oidc::button>
        </form>
    @endif

    <x-oidc::link :href="route('login')" icon="chevron-left" class="self-start">
        {{ __('oidc::screens.forgot_password.back') }}
    </x-oidc::link>
</div>
