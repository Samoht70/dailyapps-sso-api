<?php

namespace Functional\Users\Actions;

use Functional\Users\Models\User;
use Technical\Audit\Actions\RecordSecurityEvent;
use Technical\Audit\Enums\SecurityEventType;
use Technical\Oidc\Models\SsoSession;

class DisableUser
{
    public function __construct(
        private readonly EnsureAnAdministratorRemains $ensureAnAdministratorRemains,
        private readonly RecordSecurityEvent $record,
    ) {}

    public function __invoke(User $account, ?User $disabledBy = null): User
    {
        ($this->ensureAnAdministratorRemains)($account);

        $account->state()->disable();

        SsoSession::query()
            ->where('user_id', $account->getKey())
            ->alive()
            ->cursor()
            ->each(fn (SsoSession $session) => $session->revoke());

        ($this->record)(
            SecurityEventType::UserDisabled,
            actor: $disabledBy,
            organization: $account->organization,
            subject: $account,
        );

        return $account->fresh();
    }
}
