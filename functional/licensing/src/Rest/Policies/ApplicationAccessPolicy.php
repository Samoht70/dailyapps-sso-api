<?php

namespace Functional\Licensing\Rest\Policies;

use Functional\Licensing\Rest\Controls\ApplicationAccessControl;
use Lomkit\Access\Policies\ControlledPolicy;

class ApplicationAccessPolicy extends ControlledPolicy
{
    protected string $control = ApplicationAccessControl::class;
}
