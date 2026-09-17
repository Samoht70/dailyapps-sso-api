<div class="space-y-10">
    <h1 class="text-2xl font-semibold tracking-tight">{{ __('oidc::screens.account.heading') }}</h1>

    @if ($notice)
        <p class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-800" role="status">{{ $notice }}</p>
    @endif

    <form wire:submit="saveProfile" class="space-y-6">
        <div>
            <label for="name" class="block text-sm font-medium">{{ __('oidc::screens.account.name') }}</label>
            <input id="name" type="text" required wire:model="name"
                   class="mt-2 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-900 focus:outline-none">
            @error('name')
                <p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium">{{ __('oidc::screens.account.email') }}</label>
            <input id="email" type="email" required wire:model="email"
                   class="mt-2 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-900 focus:outline-none">
            @error('email')
                <p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 font-medium text-white hover:bg-gray-800">
            {{ __('oidc::screens.account.save') }}
        </button>
    </form>

    <section class="space-y-6 border-t border-gray-200 pt-10">
        <h2 class="text-lg font-semibold">{{ __('oidc::screens.account.password_heading') }}</h2>

        <form wire:submit="changePassword" class="space-y-6">
            <div>
                <label for="current_password" class="block text-sm font-medium">{{ __('oidc::screens.account.current_password') }}</label>
                <input id="current_password" type="password" autocomplete="current-password" required
                       wire:model="current_password"
                       class="mt-2 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-900 focus:outline-none">
                @error('current_password')
                    <p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="new_password" class="block text-sm font-medium">{{ __('oidc::screens.account.new_password') }}</label>
                <input id="new_password" type="password" autocomplete="new-password" required
                       wire:model="password"
                       class="mt-2 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-900 focus:outline-none">
                @error('password')
                    <p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium">{{ __('oidc::screens.account.confirmation') }}</label>
                <input id="password_confirmation" type="password" autocomplete="new-password" required
                       wire:model="password_confirmation"
                       class="mt-2 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-900 focus:outline-none">
            </div>

            <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 font-medium text-white hover:bg-gray-800">
                {{ __('oidc::screens.account.change_password') }}
            </button>
        </form>
    </section>

    <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-200 pt-10">
        @csrf
        <button type="submit" class="text-sm underline">{{ __('oidc::screens.account.logout') }}</button>
    </form>
</div>
