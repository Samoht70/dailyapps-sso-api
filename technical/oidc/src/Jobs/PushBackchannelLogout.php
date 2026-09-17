<?php

namespace Technical\Oidc\Jobs;

use Functional\Catalog\Models\Application;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Technical\Audit\Actions\RecordSecurityEvent;
use Technical\Audit\Enums\SecurityEventType;
use Technical\Oidc\Actions\BuildLogoutToken;
use Technical\Oidc\Enums\LogoutReason;
use Technical\Oidc\Models\SsoSessionParticipant;
use Throwable;

class PushBackchannelLogout implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(
        private readonly SsoSessionParticipant $participant,
        private readonly LogoutReason $reason,
    ) {}

    /**
     * One job per participant: an application that cannot be reached holds up
     * its own retries and nobody else's.
     */
    public function handle(BuildLogoutToken $buildToken, RecordSecurityEvent $record): void
    {
        $application = $this->participant->application;

        if (! $application->receivesLogoutPush()) {
            $this->recordUnreachable($record, $application, 'no_backchannel_logout_url');

            return;
        }

        Http::withBody(
            $buildToken($this->participant->ssoSession, $application, $this->reason),
            'application/jwt',
        )->post($application->backchannel_logout_url)->throw();

        $this->participant->markLogoutPushed();
    }

    public function failed(?Throwable $failure): void
    {
        app(RecordSecurityEvent::class)(
            SecurityEventType::LogoutPushFailed,
            subject: $this->participant->application,
            payload: [
                'application' => $this->participant->application->slug,
                'sid' => $this->participant->sso_session_id,
                'reason' => $this->reason->value,
                'failure' => $failure?->getMessage(),
            ],
        );
    }

    /**
     * An application that declared no address is not a failure to retry: it
     * simply never learns, and holds only until its token expires. Said out
     * loud rather than passed over, because it is the limit of SC-005.
     */
    private function recordUnreachable(
        RecordSecurityEvent $record,
        Application $application,
        string $because,
    ): void {
        $record(
            SecurityEventType::LogoutPushFailed,
            subject: $application,
            payload: [
                'application' => $application->slug,
                'sid' => $this->participant->sso_session_id,
                'reason' => $this->reason->value,
                'failure' => $because,
            ],
        );
    }
}
