<?php

namespace Functional\Catalog\Exceptions;

use Functional\Catalog\Enums\ApplicationStatus;
use RuntimeException;

class IllegalApplicationTransition extends RuntimeException
{
    public static function between(ApplicationStatus $from, ApplicationStatus $to): self
    {
        return new self("An application cannot move from {$from->value} to {$to->value}.");
    }
}
