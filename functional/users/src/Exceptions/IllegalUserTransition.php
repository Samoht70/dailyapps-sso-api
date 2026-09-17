<?php

namespace Functional\Users\Exceptions;

use Functional\Users\Enums\UserStatus;
use RuntimeException;
use Technical\Framework\Exceptions\BusinessRule;

class IllegalUserTransition extends RuntimeException implements BusinessRule
{
    public function machineCode(): string
    {
        return 'illegal_transition';
    }

    public static function between(UserStatus $from, UserStatus $to): self
    {
        return new self("A user account cannot move from {$from->value} to {$to->value}.");
    }
}
