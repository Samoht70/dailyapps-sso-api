<?php

namespace Functional\Licensing\Actions;

use Functional\Catalog\Models\Application;
use Functional\Licensing\Exceptions\NoValidLicense;
use Functional\Licensing\Exceptions\SeatsExhausted;
use Functional\Licensing\Models\ApplicationAccess;
use Functional\Licensing\Models\License;
use Functional\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Technical\Audit\Actions\RecordSecurityEvent;
use Technical\Audit\Enums\SecurityEventType;

class GrantApplicationAccess
{
    public function __construct(
        private readonly AssignApplicationRoles $assignRoles,
        private readonly RecordSecurityEvent $record,
    ) {}

    /**
     * The licence row is locked before its seats are counted: without the lock,
     * two administrators granting the last seat at once both read it free.
     *
     * @param  list<string>  $roleKeys
     *
     * @throws NoValidLicense|SeatsExhausted
     */
    public function __invoke(
        User $account,
        Application $application,
        ?User $grantedBy = null,
        array $roleKeys = [],
    ): ApplicationAccess {
        return DB::transaction(function () use ($account, $application, $grantedBy, $roleKeys): ApplicationAccess {
            $existing = ApplicationAccess::query()
                ->where('user_id', $account->getKey())
                ->where('application_id', $application->getKey())
                ->first();

            if ($existing !== null) {
                ($this->assignRoles)($existing, $roleKeys);

                return $existing;
            }

            $license = License::query()
                ->where('organization_id', $account->organization_id)
                ->where('application_id', $application->getKey())
                ->lockForUpdate()
                ->first();

            if ($license === null || ! $license->isValid()) {
                throw NoValidLicense::on($application);
            }

            if (! $license->hasFreeSeat()) {
                throw SeatsExhausted::on($license);
            }

            $access = ApplicationAccess::query()->create([
                'user_id' => $account->getKey(),
                'application_id' => $application->getKey(),
                'granted_by_id' => $grantedBy?->getKey(),
                'granted_at' => now(),
            ]);

            ($this->assignRoles)($access, $roleKeys);

            ($this->record)(
                SecurityEventType::AccessGranted,
                actor: $grantedBy,
                organization: $account->organization,
                subject: $access,
                payload: ['application' => $application->slug, 'user' => $account->getKey()],
            );

            return $access;
        });
    }
}
