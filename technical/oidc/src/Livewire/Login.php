<?php

namespace Technical\Oidc\Livewire;

use Functional\Users\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Technical\Oidc\Actions\ThrottleAuthentication;
use Technical\Oidc\Exceptions\AuthenticationThrottled;

#[Layout('oidc::components.layouts.screen')]
class Login extends Component
{
    private const ABSENT_ACCOUNT_HASH = '$2y$12$T2BB9gWZ7WbfaHZ8kLRKn.dTKrzEoR5m6pMSK.KhBbGLSYHGnRVAm';

    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function authenticate(ThrottleAuthentication $throttle): void
    {
        $this->validate();

        $origin = request()->ip() ?? 'unknown';

        try {
            $throttle->ensureIsNotLocked($this->email, $origin);
        } catch (AuthenticationThrottled $throttled) {
            $this->fail(__('oidc::auth.throttled', ['seconds' => $throttled->secondsRemaining]));

            return;
        }

        $account = User::query()->where('email', $this->email)->first();

        if (! $this->passwordMatches($account)) {
            $throttle->recordFailure($this->email, $origin);
            event(new Failed(config('passport.guard'), $account, ['email' => $this->email]));

            $this->fail(__('oidc::auth.failed'));

            return;
        }

        if (! $account->canAuthenticate()) {
            $this->fail(__('oidc::auth.account_unavailable'));

            return;
        }

        if (! $account->organization->isActive()) {
            $this->fail(__('oidc::auth.organization_suspended'));

            return;
        }

        $throttle->forget($this->email, $origin);

        Auth::login($account, $this->remember);
        session()->regenerate();

        $this->redirectIntended(route('account'), navigate: true);
    }

    public function render(): View
    {
        return view('oidc::livewire.login');
    }

    /**
     * Hashing is attempted even when no account matches, so the time the screen
     * takes to answer does not tell an attacker the address exists.
     */
    private function passwordMatches(?User $account): bool
    {
        $matches = Hash::check($this->password, $account?->password ?? self::ABSENT_ACCOUNT_HASH);

        return $account !== null && $matches;
    }

    private function fail(string $reason): void
    {
        $this->reset('password');
        $this->addError('email', $reason);
    }
}
