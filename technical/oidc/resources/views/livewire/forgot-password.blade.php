<div>
    <x-oidc::heading>{{ __('oidc::screens.forgot_password.heading') }}</x-oidc::heading>

    @if ($sent)
        <x-oidc::alert tone="success" :title="__('oidc::screens.forgot_password.sent_title')" class="mt-6">
            {{ __('oidc::screens.forgot_password.sent') }}
        </x-oidc::alert>

        <x-oidc::hint class="mt-4">
            {{ __('oidc::screens.forgot_password.expiry_hint', ['minutes' => config('auth.passwords.users.expire')]) }}
        </x-oidc::hint>

        <x-oidc::link :href="route('login')" icon="chevron-left" class="mt-8">
            {{ __('oidc::screens.forgot_password.back') }}
        </x-oidc::link>
    @else
        <p class="mt-4 text-sm/5 text-body">{{ __('oidc::screens.forgot_password.intro') }}</p>

        <form wire:submit="sendLink" class="mt-8 flex flex-col gap-6">
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

        <x-oidc::link :href="route('login')" icon="chevron-left" class="mt-8">
            {{ __('oidc::screens.forgot_password.back') }}
        </x-oidc::link>
    @endif
</div>
