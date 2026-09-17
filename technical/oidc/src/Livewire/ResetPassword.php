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

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
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
}
