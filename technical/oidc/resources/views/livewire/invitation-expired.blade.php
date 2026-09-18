<x-oidc::layouts.screen :title="__('oidc::screens.link_expired.title')">
    <x-oidc::heading>{{ __('oidc::screens.link_expired.invitation_heading') }}</x-oidc::heading>

    <x-oidc::alert tone="warning" class="mt-6">
        {{ __('oidc::screens.invitation.expired') }}
    </x-oidc::alert>

    <p class="mt-6 text-sm/5 text-body">{{ __('oidc::screens.link_expired.ask_administrator') }}</p>

    <x-oidc::link :href="route('login')" icon="chevron-left" :navigate="false" class="mt-8">
        {{ __('oidc::screens.link_expired.back') }}
    </x-oidc::link>
</x-oidc::layouts.screen>
