<?php

namespace Technical\Oidc\Actions;

use Technical\Audit\Actions\RecordSecurityEvent;
use Technical\Audit\Enums\SecurityEventType;
use Technical\Oidc\Enums\LogoutReason;
use Technical\Oidc\Jobs\PushBackchannelLogout;
use Technical\Oidc\Models\SsoSession;
use Technical\Oidc\Models\SsoSessionParticipant;

class EndSsoSession
{
    public function __construct(
        private readonly RevokeSessionTokens $revokeTokens,
        private readonly RecordSecurityEvent $record,
    ) {}

    /**
     * The single way a session ends, whatever set it off — a logout, a disabled
     * account, a suspended organization or a changed password. Participants are
     * pushed in a stable order so a run can be replayed and compared.
     */
    public function __invoke(SsoSession $session, LogoutReason $reason): void
    {
        if (! $session->isAlive()) {
            return;
        }

        $session->revoke();
        ($this->revokeTokens)($session);

        $session->participants()
            ->orderBy('application_id')
            ->cursor()
            ->each(fn (SsoSessionParticipant $participant) => PushBackchannelLogout::dispatch($participant, $reason));

        ($this->record)(
            SecurityEventType::SessionEnded,
            organization: $session->user?->organization,
            subject: $session->user,
            payload: ['sid' => $session->getKey(), 'reason' => $reason->value],
        );
    }
}
