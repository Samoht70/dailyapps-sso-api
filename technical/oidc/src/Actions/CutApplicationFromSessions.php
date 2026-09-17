<?php

namespace Technical\Oidc\Actions;

use Functional\Catalog\Models\Application;
use Functional\Organizations\Models\Organization;
use Technical\Oidc\Enums\LogoutReason;
use Technical\Oidc\Jobs\PushBackchannelLogout;
use Technical\Oidc\Models\SsoSession;
use Technical\Oidc\Models\SsoSessionParticipant;

class CutApplicationFromSessions
{
    public function __construct(private readonly RevokeSessionTokens $revokeTokens) {}

    /**
     * A revoked licence does not end the session — the person keeps the other
     * applications open. Only the one they lost is told to close.
     */
    public function __invoke(
        Application $application,
        Organization $organization,
        LogoutReason $reason,
    ): void {
        SsoSession::query()
            ->alive()
            ->whereIn('user_id', $organization->users()->select('id'))
            ->orderBy('id')
            ->cursor()
            ->each(function (SsoSession $session) use ($application, $reason): void {
                ($this->revokeTokens)($session, $application);

                $session->participants()
                    ->where('application_id', $application->getKey())
                    ->cursor()
                    ->each(fn (SsoSessionParticipant $participant) => PushBackchannelLogout::dispatch($participant, $reason));
            });
    }
}
