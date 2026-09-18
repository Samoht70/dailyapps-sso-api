<x-oidc::layouts.screen :title="__('oidc::screens.link_expired.title')">
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-4">
            <x-oidc::clock />
            <x-oidc::heading>{{ __('oidc::screens.link_expired.invitation_heading') }}</x-oidc::heading>
        </div>

        <x-oidc::alert tone="warning" :title="__('oidc::screens.link_expired.alert_title')">
            {{ __('oidc::screens.invitation.expired') }}
        </x-oidc::alert>

        <p class="text-sm/5 text-muted">{{ __('oidc::screens.link_expired.ask_administrator') }}</p>

        <x-oidc::link :href="route('login')" icon="chevron-left" :navigate="false" class="self-start">
            {{ __('oidc::screens.link_expired.back') }}
        </x-oidc::link>
    </div>
</x-oidc::layouts.screen>
