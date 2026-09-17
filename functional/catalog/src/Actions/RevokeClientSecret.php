<?php

namespace Functional\Catalog\Actions;

use Functional\Catalog\Models\Application;
use Functional\Users\Models\User;
use Technical\Audit\Actions\RecordSecurityEvent;
use Technical\Audit\Enums\SecurityEventType;

class RevokeClientSecret
{
    public function __construct(private readonly RecordSecurityEvent $record) {}

    /**
     * The client is left without a usable secret, so it can no longer exchange
     * a code. The other applications carry on untouched.
     */
    public function __invoke(Application $application, ?User $revokedBy = null): void
    {
        $application->oauthClient->forceFill(['revoked' => true, 'secret' => null])->save();

        ($this->record)(
            SecurityEventType::ClientSecretRotated,
            actor: $revokedBy,
            subject: $application,
            payload: ['application' => $application->slug, 'revoked' => true],
        );
    }
}
