<?php

namespace Functional\Catalog\Rest\Policies;

use Functional\Catalog\Rest\Controls\ApplicationRoleControl;
use Lomkit\Access\Policies\ControlledPolicy;

class ApplicationRolePolicy extends ControlledPolicy
{
    protected string $control = ApplicationRoleControl::class;
}
