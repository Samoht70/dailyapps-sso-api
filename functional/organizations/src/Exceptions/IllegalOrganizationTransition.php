<?php

namespace Functional\Organizations\Exceptions;

use Functional\Organizations\Enums\OrganizationStatus;
use RuntimeException;

class IllegalOrganizationTransition extends RuntimeException
{
    public static function between(OrganizationStatus $from, OrganizationStatus $to): self
    {
        return new self("An organization cannot move from {$from->value} to {$to->value}.");
    }

    public static function operatorCannotBeSuspended(): self
    {
        return new self('The operator organization cannot be suspended.');
    }
}
