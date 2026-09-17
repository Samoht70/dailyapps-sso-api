<?php

namespace Functional\Users\Enums;

use Functional\Users\States\ActiveState;
use Functional\Users\States\DisabledState;
use Functional\Users\States\InvitedState;
use Functional\Users\States\UserState;

enum UserStatus: string
{
    case Invited = 'invited';
    case Active = 'active';
    case Disabled = 'disabled';

    /** @return class-string<UserState> */
    public function stateClass(): string
    {
        return match ($this) {
            self::Invited => InvitedState::class,
            self::Active => ActiveState::class,
            self::Disabled => DisabledState::class,
        };
    }
}
