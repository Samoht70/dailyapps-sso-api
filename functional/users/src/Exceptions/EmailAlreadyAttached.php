<?php

namespace Functional\Users\Exceptions;

use RuntimeException;
use Technical\Framework\Exceptions\BusinessRule;

class EmailAlreadyAttached extends RuntimeException implements BusinessRule
{
    public function machineCode(): string
    {
        return 'email_already_attached';
    }

    public static function to(string $email): self
    {
        return new self("The address {$email} already belongs to an organization.");
    }
}
