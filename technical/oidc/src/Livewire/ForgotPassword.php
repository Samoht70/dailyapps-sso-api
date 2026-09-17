<?php

namespace Technical\Oidc\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('oidc::components.layouts.screen')]
class ForgotPassword extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    public bool $sent = false;

    /**
     * The broker's answer is deliberately dropped: telling a known address from
     * an unknown one would turn this screen into an account-existence oracle.
     */
    public function sendLink(): void
    {
        $this->validate();

        Password::sendResetLink(['email' => $this->email]);

        $this->sent = true;
    }

    public function render(): View
    {
        return view('oidc::livewire.forgot-password');
    }
}
