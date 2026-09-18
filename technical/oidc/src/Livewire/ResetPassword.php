<?php

namespace Technical\Oidc\Livewire;

use Functional\Users\Events\PasswordChanged;
use Functional\Users\Models\User;
use Functional\Users\Rules\PasswordStrength;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('oidc::components.layouts.screen')]
class ResetPassword extends Component
{
    #[Locked]
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    #[Locked]
    public bool $expired = false;

    /**
     * The token is only read when the address travels with the link, which the
     * mailed URL always carries. A link retyped without it falls back to the
     * form, where the submission stays the guard.
     */
    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
        $this->expired = $this->email !== '' && ! $this->tokenIsAlive();
    }

    public function resetPassword(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', new PasswordStrength],
        ]);

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function (User $user, string $password): void {
                $user->forceFill(['password' => Hash::make($password)])->save();

                PasswordChanged::dispatch($user);
            },
        );

        if ($status !== Password::PasswordReset) {
            $this->addError('email', __('oidc::screens.reset_password.invalid'));

            return;
        }

        $this->redirectRoute('login', navigate: true);
    }

    public function render(): View
    {
        return view('oidc::livewire.reset-password');
    }

    /**
     * Reads the token without spending it. An unknown address and a wrong token
     * answer alike, so the screen never tells that an account exists.
     */
    private function tokenIsAlive(): bool
    {
        $account = Password::broker()->getUser(['email' => $this->email]);

        return $account !== null
            && Password::broker()->getRepository()->exists($account, $this->token);
    }
}
