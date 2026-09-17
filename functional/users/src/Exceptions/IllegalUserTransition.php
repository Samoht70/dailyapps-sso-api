<?php

namespace Functional\Users\Exceptions;

use Functional\Users\Enums\UserStatus;
use RuntimeException;

class IllegalUserTransition extends RuntimeException
{
    public static function between(UserStatus $from, UserStatus $to): self
    {
        return new self("A user account cannot move from {$from->value} to {$to->value}.");
    }
}
