<?php

namespace Functional\Users\States;

use Functional\Users\Enums\UserStatus;
use Functional\Users\Models\User;

class DisabledState extends UserState
{
    public function status(): UserStatus
    {
        return UserStatus::Disabled;
    }

    public function canAuthenticate(): bool
    {
        return false;
    }

    public function appearsInAccesses(): bool
    {
        return false;
    }

    public function consumesSeat(): bool
    {
        return false;
    }

    public function activate(): User
    {
        return $this->moveTo(UserStatus::Active);
    }
}
