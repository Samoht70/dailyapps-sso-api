<?php

namespace Functional\Licensing\Actions;

use Functional\Licensing\Models\License;
use Functional\Users\Models\User;
use Technical\Audit\Actions\RecordSecurityEvent;
use Technical\Audit\Enums\SecurityEventType;
use Technical\Oidc\Actions\CutApplicationFromSessions;
use Technical\Oidc\Enums\LogoutReason;

class RevokeLicense
{
    public function __construct(
        private readonly CutApplicationFromSessions $cutApplication,
        private readonly RecordSecurityEvent $record,
    ) {}

    public function __invoke(License $license, ?User $revokedBy = null): void
    {
        ($this->record)(
            SecurityEventType::LicenseRevoked,
            actor: $revokedBy,
            organization: $license->organization,
            payload: ['application' => $license->application->slug],
        );

        ($this->cutApplication)(
            $license->application,
            $license->organization,
            LogoutReason::LicenseRevoked,
        );

        $license->delete();
    }
}
