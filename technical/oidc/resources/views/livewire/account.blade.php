@php
    $profileSaved = $notice === __('oidc::screens.account.saved');
    $passwordChanged = $notice === __('oidc::screens.account.password_changed');
@endphp

<div class="flex flex-col gap-8">
    <x-oidc::heading>{{ __('oidc::screens.account.heading') }}</x-oidc::heading>

    <x-oidc::card :heading="__('oidc::screens.account.info_heading')">
        @if ($profileSaved)
            <x-oidc::alert tone="success" :title="__('oidc::screens.account.saved_title')" class="mb-6">
                {{ $notice }}
            </x-oidc::alert>
        @endif

        <form wire:submit="saveProfile" class="flex flex-col gap-6">
            <x-oidc::field
                name="name"
                :label="__('oidc::screens.account.name')"
                :size="40"
                autocomplete="name"
                required />

            <x-oidc::field
                name="email"
                type="email"
                :label="__('oidc::screens.account.email')"
                :size="40"
                autocomplete="email"
                required />

            <div>
                <x-oidc::button :size="40">{{ __('oidc::screens.account.save') }}</x-oidc::button>
            </div>
        </form>
    </x-oidc::card>

    <x-oidc::card :heading="__('oidc::screens.account.password_heading')">
        @if ($passwordChanged)
            <x-oidc::alert tone="success" :title="__('oidc::screens.account.password_changed_title')" class="mb-6">
                {{ $notice }}
            </x-oidc::alert>
        @endif

        <form wire:submit="changePassword" class="flex flex-col gap-6">
            <x-oidc::field
                name="current_password"
                type="password"
                :label="__('oidc::screens.account.current_password')"
                :size="40"
                autocomplete="current-password"
                required />

            <x-oidc::field
                name="password"
                type="password"
                :label="__('oidc::screens.account.new_password')"
                :size="40"
                :hint="__('oidc::screens.account.password_hint', ['min' => \Functional\Users\Rules\PasswordStrength::MINIMUM_LENGTH])"
                autocomplete="new-password"
                required />

            <x-oidc::field
                name="password_confirmation"
                type="password"
                :label="__('oidc::screens.account.confirmation')"
                :size="40"
                autocomplete="new-password"
                required />

            <div>
                <x-oidc::button :size="40">{{ __('oidc::screens.account.change_password') }}</x-oidc::button>
            </div>
        </form>
    </x-oidc::card>
</div>
