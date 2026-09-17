<?php

namespace Functional\Licensing\Exceptions;

use Functional\Catalog\Models\ApplicationRole;
use RuntimeException;
use Technical\Framework\Exceptions\BusinessRule;

class RoleFromAnotherApplication extends RuntimeException implements BusinessRule
{
    public function machineCode(): string
    {
        return 'role_from_another_application';
    }

    public static function is(ApplicationRole $role): self
    {
        return new self("The role {$role->key} belongs to another application.");
    }
}
