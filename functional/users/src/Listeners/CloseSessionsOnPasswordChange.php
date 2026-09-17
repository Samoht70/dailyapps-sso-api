<?php

namespace Functional\Users\Listeners;

use Functional\Users\Events\PasswordChanged;
use Technical\Oidc\Models\SsoSession;

class CloseSessionsOnPasswordChange
{
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
            ->cursor()
            ->each(fn (SsoSession $session) => $session->revoke());
    }
}
