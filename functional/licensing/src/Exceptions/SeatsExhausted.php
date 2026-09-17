<?php

namespace Functional\Licensing\Exceptions;

use Functional\Licensing\Models\License;
use RuntimeException;
use Technical\Framework\Exceptions\BusinessRule;

class SeatsExhausted extends RuntimeException implements BusinessRule
{
    public function machineCode(): string
    {
        return 'seats_exhausted';
    }

    public static function on(License $license): self
    {
        return new self("The licence holds {$license->seats} seats and every one of them is taken.");
    }
}
