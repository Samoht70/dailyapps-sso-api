<?php

namespace Technical\Audit\Rest\Policies;

use Lomkit\Access\Policies\ControlledPolicy;
use Technical\Audit\Rest\Controls\SecurityEventControl;

class SecurityEventPolicy extends ControlledPolicy
{
    protected string $control = SecurityEventControl::class;
}
