<?php

namespace Technical\Oidc\Livewire;

use Functional\Users\Models\Invitation;
use Functional\Users\Rules\PasswordStrength;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Technical\Audit\Actions\RecordSecurityEvent;
use Technical\Audit\Enums\SecurityEventType;

#[Layout('oidc::components.layouts.screen')]
class AcceptInvitation extends Component
{
    #[Locked]
    public string $token = '';

    #[Locked]
    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * An invitation that has expired or has already been spent answers `410`:
     * it is gone, not merely refused, and asking again will not help.
     */
    public function mount(string $token): void
    {
        $invitation = $this->invitation($token);

        abort_if($invitation === null, 410);

        $this->token = $token;
        $this->email = $invitation->email;
    }

    public function activate(RecordSecurityEvent $record): void
    {
        $this->validate([
            'password' => ['required', 'string', 'confirmed', new PasswordStrength],
        ]);

        $invitation = $this->invitation($this->token);

        if ($invitation === null) {
            $this->addError('password', __('oidc::screens.invitation.expired'));

            return;
        }

        $account = $invitation->accept($this->password);

        $record(
            SecurityEventType::InvitationAccepted,
            actor: $account,
            organization: $account->organization,
            subject: $invitation,
        );

        Auth::login($account);
        session()->regenerate();

        $this->redirectIntended(route('account'), navigate: true);
    }

    public function render(): View
    {
        return view('oidc::livewire.accept-invitation')->layoutData([
            'headline' => __('oidc::screens.brand.invitation_headline'),
            'tagline' => __('oidc::screens.brand.invitation_tagline'),
        ]);
    }

    private function invitation(string $token): ?Invitation
    {
        return Invitation::query()
            ->where('token_hash', Invitation::hash($token))
            ->pending()
            ->first();
    }
}
