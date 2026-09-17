<div>
    <h1 class="text-2xl font-semibold tracking-tight">{{ __('oidc::screens.forgot_password.heading') }}</h1>

    @if ($sent)
        <p class="mt-8 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800" role="status">
            {{ __('oidc::screens.forgot_password.sent') }}
        </p>

        <a href="{{ route('login') }}" wire:navigate class="mt-6 inline-block text-sm underline">
            {{ __('oidc::screens.forgot_password.back') }}
        </a>
    @else
        <form wire:submit="sendLink" class="mt-8 space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium">{{ __('oidc::screens.forgot_password.email') }}</label>
                <input id="email" type="email" autocomplete="username" required autofocus
                       wire:model="email"
                       class="mt-2 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-900 focus:outline-none">
                @error('email')
                    <p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full rounded-md bg-gray-900 px-4 py-2 font-medium text-white hover:bg-gray-800">
                {{ __('oidc::screens.forgot_password.submit') }}
            </button>
        </form>
    @endif
</div>
