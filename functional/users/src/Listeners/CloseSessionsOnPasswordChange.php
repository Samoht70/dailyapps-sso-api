<?php

namespace Functional\Users\Listeners;

use Functional\Users\Events\PasswordChanged;
use Technical\Oidc\Actions\EndSsoSession;
use Technical\Oidc\Enums\LogoutReason;
use Technical\Oidc\Models\SsoSession;

class CloseSessionsOnPasswordChange
{
    public function __construct(private readonly EndSsoSession $endSession) {}

    /**
     * The session the change was made from survives, so the user is not thrown
     * out of the screen they just used.
     */
    public function handle(PasswordChanged $event): void
    {
        SsoSession::query()
            ->where('user_id', $event->user->getKey())
            ->when($event->keptSsoSessionId, fn ($query) => $query->whereKeyNot($event->keptSsoSessionId))
            ->alive()
            ->orderBy('id')
            ->cursor()
            ->each(fn (SsoSession $session) => ($this->endSession)($session, LogoutReason::PasswordChanged));
    }
}
