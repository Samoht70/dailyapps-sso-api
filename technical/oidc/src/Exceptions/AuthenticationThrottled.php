<?php

namespace Technical\Oidc\Exceptions;

use RuntimeException;

class AuthenticationThrottled extends RuntimeException
{
    private function __construct(public readonly int $secondsRemaining)
    {
        parent::__construct("Authentication is locked for {$secondsRemaining} more seconds.");
    }

    public static function forAnother(int $seconds): self
    {
        return new self($seconds);
    }
}
