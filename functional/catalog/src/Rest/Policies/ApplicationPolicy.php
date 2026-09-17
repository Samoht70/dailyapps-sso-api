<?php

namespace Functional\Catalog\Rest\Policies;

use Functional\Catalog\Rest\Controls\ApplicationControl;
use Lomkit\Access\Policies\ControlledPolicy;

class ApplicationPolicy extends ControlledPolicy
{
    protected string $control = ApplicationControl::class;
}
