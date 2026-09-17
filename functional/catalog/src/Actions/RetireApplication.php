<?php

namespace Functional\Catalog\Actions;

use Functional\Catalog\Models\Application;
use Functional\Users\Models\User;
use Technical\Audit\Actions\RecordSecurityEvent;
use Technical\Audit\Enums\SecurityEventType;

class RetireApplication
{
    public function __construct(private readonly RecordSecurityEvent $record) {}

    /**
     * Retiring drops the application out of the rights answered; the accesses
     * granted in the past stay in place, so putting it back needs no re-grant.
     */
    public function __invoke(Application $application, ?User $retiredBy = null): Application
    {
        $application->retire();

        ($this->record)(
            SecurityEventType::ApplicationRetired,
            actor: $retiredBy,
            subject: $application,
            payload: ['application' => $application->slug],
        );

        return $application->fresh();
    }
}
