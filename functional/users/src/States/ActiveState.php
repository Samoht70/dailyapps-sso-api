<?php

namespace Functional\Users\States;

use Functional\Users\Enums\UserStatus;
use Functional\Users\Models\User;

class ActiveState extends UserState
{
    public function status(): UserStatus
    {
        return UserStatus::Active;
    }

    public function canAuthenticate(): bool
    {
        return true;
    }

    public function appearsInAccesses(): bool
    {
        return true;
    }

    public function consumesSeat(): bool
    {
        return true;
    }

    public function disable(): User
    {
        return $this->moveTo(UserStatus::Disabled);
    }
}
