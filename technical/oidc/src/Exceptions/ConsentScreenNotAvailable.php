<?php

namespace Technical\Oidc\Exceptions;

use RuntimeException;

class ConsentScreenNotAvailable extends RuntimeException
{
    public static function forClient(string $name): self
    {
        return new self(
            "The client [{$name}] asked for a consent screen. Every client of this ecosystem is "
            .'first-party and skips consent, so this one was declared with an owner by mistake.'
        );
    }
}
