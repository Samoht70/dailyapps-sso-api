<?php

namespace Technical\Framework\Exceptions;

interface BusinessRule
{
    /**
     * A stable code a client branches on, never a message it has to parse.
     */
    public function machineCode(): string;
}
