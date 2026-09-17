<?php

namespace Functional\Licensing\Actions;

use Functional\Licensing\Models\ApplicationAccess;
use Functional\Users\Models\User;
use Technical\Audit\Actions\RecordSecurityEvent;
use Technical\Audit\Enums\SecurityEventType;

class RevokeApplicationAccess
{
    public function __construct(private readonly RecordSecurityEvent $record) {}

    public function __invoke(ApplicationAccess $access, ?User $revokedBy = null): void
    {
        ($this->record)(
            SecurityEventType::AccessRevoked,
            actor: $revokedBy,
            organization: $access->user->organization,
            payload: [
                'application' => $access->application->slug,
                'user' => $access->user_id,
            ],
        );

        $access->roles()->detach();
        $access->delete();
    }
}
