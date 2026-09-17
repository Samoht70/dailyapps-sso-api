<?php

namespace Functional\Licensing\Exceptions;

use Functional\Catalog\Models\Application;
use RuntimeException;
use Technical\Framework\Exceptions\BusinessRule;

class NoValidLicense extends RuntimeException implements BusinessRule
{
    public function machineCode(): string
    {
        return 'no_valid_license';
    }

    public static function on(Application $application): self
    {
        return new self("The organization holds no valid licence on {$application->slug}.");
    }
}
