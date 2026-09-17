<?php

namespace Functional\Users\Exceptions;

use Functional\Organizations\Models\Organization;
use RuntimeException;
use Technical\Framework\Exceptions\BusinessRule;

class LastAdministrator extends RuntimeException implements BusinessRule
{
    public function machineCode(): string
    {
        return 'last_admin';
    }

    public static function of(Organization $organization): self
    {
        return new self(
            "{$organization->name} would be left without a single active administrator.",
        );
    }
}
