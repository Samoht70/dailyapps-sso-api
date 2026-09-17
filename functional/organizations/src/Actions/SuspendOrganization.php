<?php

namespace Functional\Organizations\Actions;

use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Technical\Audit\Actions\RecordSecurityEvent;
use Technical\Audit\Enums\SecurityEventType;
use Technical\Oidc\Actions\EndSsoSession;
use Technical\Oidc\Enums\LogoutReason;
use Technical\Oidc\Models\SsoSession;

class SuspendOrganization
{
    public function __construct(
        private readonly EndSsoSession $endSession,
        private readonly RecordSecurityEvent $record,
    ) {}

    /**
     * Suspension cuts every user of the organization, its own administrators
     * included: leaving them in would leave a way back into a customer that has
     * been cut off.
     */
    public function __invoke(Organization $organization, ?User $suspendedBy = null): Organization
    {
        $organization->suspend();

        SsoSession::query()
            ->whereIn('user_id', $organization->users()->select('id'))
            ->alive()
            ->orderBy('id')
            ->cursor()
            ->each(fn (SsoSession $session) => ($this->endSession)($session, LogoutReason::OrganizationSuspended));

        ($this->record)(
            SecurityEventType::OrganizationSuspended,
            actor: $suspendedBy,
            organization: $organization,
            subject: $organization,
        );

        return $organization->fresh();
    }
}
