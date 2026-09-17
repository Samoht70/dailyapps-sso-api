<?php

namespace Functional\Catalog\Actions;

use Functional\Catalog\Models\Application;
use Functional\Users\Models\User;
use Illuminate\Support\Str;
use Technical\Audit\Actions\RecordSecurityEvent;
use Technical\Audit\Enums\SecurityEventType;

class RotateClientSecret
{
    public function __construct(private readonly RecordSecurityEvent $record) {}

    /**
     * Rotation touches one client and no other: fifteen applications do not go
     * down because one of them changed its secret.
     *
     * @return string the new secret, readable this once and never again
     */
    public function __invoke(Application $application, ?User $rotatedBy = null): string
    {
        $client = $application->oauthClient;

        $client->forceFill(['secret' => $secret = Str::random(40)])->save();

        ($this->record)(
            SecurityEventType::ClientSecretRotated,
            actor: $rotatedBy,
            subject: $application,
            payload: ['application' => $application->slug],
        );

        return $secret;
    }
}
