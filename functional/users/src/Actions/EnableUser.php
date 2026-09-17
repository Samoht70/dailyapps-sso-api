<?php

namespace Functional\Users\Actions;

use Functional\Users\Models\User;
use Technical\Audit\Actions\RecordSecurityEvent;
use Technical\Audit\Enums\SecurityEventType;

class EnableUser
{
    public function __construct(private readonly RecordSecurityEvent $record) {}

    public function __invoke(User $account, ?User $enabledBy = null): User
    {
        $account->state()->activate();

        ($this->record)(
            SecurityEventType::UserEnabled,
            actor: $enabledBy,
            organization: $account->organization,
            subject: $account,
        );

        return $account->fresh();
    }
}
