<?php

namespace Functional\Licensing\Actions;

use Functional\Catalog\Models\Application;
use Functional\Licensing\Models\License;
use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Technical\Audit\Actions\RecordSecurityEvent;
use Technical\Audit\Enums\SecurityEventType;

class AttachLicense
{
    public function __construct(private readonly RecordSecurityEvent $record) {}

    /**
     * A renewal moves `ends_on`; it never opens a second licence, or "is the
     * licence valid" becomes a question with several answers.
     */
    public function __invoke(
        Organization $organization,
        Application $application,
        string $startsOn,
        ?string $endsOn,
        int $seats,
        ?User $attachedBy = null,
    ): License {
        $license = License::query()->updateOrCreate(
            ['organization_id' => $organization->getKey(), 'application_id' => $application->getKey()],
            ['starts_on' => $startsOn, 'ends_on' => $endsOn, 'seats' => $seats],
        );

        ($this->record)(
            SecurityEventType::LicenseAttached,
            actor: $attachedBy,
            organization: $organization,
            subject: $license,
            payload: ['application' => $application->slug, 'seats' => $seats],
        );

        return $license;
    }
}
