<?php

namespace Functional\Catalog\Exceptions;

use Functional\Catalog\Enums\ApplicationStatus;
use RuntimeException;
use Technical\Framework\Exceptions\BusinessRule;

class IllegalApplicationTransition extends RuntimeException implements BusinessRule
{
    public function machineCode(): string
    {
        return 'illegal_transition';
    }

    public static function between(ApplicationStatus $from, ApplicationStatus $to): self
    {
        return new self("An application cannot move from {$from->value} to {$to->value}.");
    }
}
