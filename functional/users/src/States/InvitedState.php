<?php

namespace Functional\Users\States;

use Functional\Users\Enums\UserStatus;
use Functional\Users\Models\User;

class InvitedState extends UserState
{
    public function status(): UserStatus
    {
        return UserStatus::Invited;
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
        return true;
    }

    public function activate(): User
    {
        return $this->moveTo(UserStatus::Active);
    }
}
