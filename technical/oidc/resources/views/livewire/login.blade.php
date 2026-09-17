<div>
    <h1 class="text-2xl font-semibold tracking-tight">{{ __('oidc::screens.login.heading') }}</h1>

    <form wire:submit="authenticate" class="mt-8 space-y-6">
        <div>
            <label for="email" class="block text-sm font-medium">{{ __('oidc::screens.login.email') }}</label>
            <input id="email" type="email" autocomplete="username" required autofocus
                   wire:model="email"
                   class="mt-2 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-900 focus:outline-none">
            @error('email')
                <p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium">{{ __('oidc::screens.login.password') }}</label>
            <input id="password" type="password" autocomplete="current-password" required
                   wire:model="password"
                   class="mt-2 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-900 focus:outline-none">
            @error('password')
                <p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="remember" class="rounded border-gray-300">
                {{ __('oidc::screens.login.remember') }}
            </label>

            <a href="{{ route('password.forgot') }}" wire:navigate class="text-sm underline">
                {{ __('oidc::screens.login.forgot') }}
            </a>
        </div>

        <button type="submit"
                class="w-full rounded-md bg-gray-900 px-4 py-2 font-medium text-white hover:bg-gray-800">
            {{ __('oidc::screens.login.submit') }}
        </button>
    </form>
</div>
