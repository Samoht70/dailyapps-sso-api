<div>
    <h1 class="text-2xl font-semibold tracking-tight">{{ __('oidc::screens.invitation.heading') }}</h1>

    <p class="mt-2 text-sm text-gray-600">{{ $email }}</p>

    <form wire:submit="activate" class="mt-8 space-y-6">
        <div>
            <label for="password" class="block text-sm font-medium">{{ __('oidc::screens.invitation.password') }}</label>
            <input id="password" type="password" autocomplete="new-password" required autofocus
                   wire:model="password"
                   class="mt-2 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-900 focus:outline-none">
            @error('password')
                <p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium">{{ __('oidc::screens.invitation.confirmation') }}</label>
            <input id="password_confirmation" type="password" autocomplete="new-password" required
                   wire:model="password_confirmation"
                   class="mt-2 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-900 focus:outline-none">
        </div>

        <button type="submit"
                class="w-full rounded-md bg-gray-900 px-4 py-2 font-medium text-white hover:bg-gray-800">
            {{ __('oidc::screens.invitation.submit') }}
        </button>
    </form>
</div>
