<?php

namespace Technical\Oidc\Livewire;

use Functional\Users\Events\PasswordChanged;
use Functional\Users\Models\User;
use Functional\Users\Rules\PasswordStrength;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Technical\Oidc\Models\SsoSession;

#[Layout('oidc::components.layouts.screen')]
class Account extends Component
{
    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $notice = null;

    public function mount(): void
    {
        $this->name = $this->account()->name;
        $this->email = $this->account()->email;
    }

    public function saveProfile(): void
    {
        $account = $this->account();

        $this->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignoreModel($account)],
        ]);

        $account->update(['name' => $this->name, 'email' => $this->email]);

        $this->notice = __('oidc::screens.account.saved');
    }

    public function changePassword(): void
    {
        $account = $this->account();

        $this->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'confirmed', new PasswordStrength],
        ]);

        $account->forceFill(['password' => Hash::make($this->password)])->save();

        PasswordChanged::dispatch($account, session()->get(SsoSession::SESSION_KEY));

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->notice = __('oidc::screens.account.password_changed');
    }

    public function render(): View
    {
        return view('oidc::livewire.account');
    }

    private function account(): User
    {
        return Auth::user();
    }
}
