<?php

namespace Functional\Organizations\Rest\Policies;

use Functional\Organizations\Rest\Controls\OrganizationControl;
use Lomkit\Access\Policies\ControlledPolicy;

class OrganizationPolicy extends ControlledPolicy
{
    protected string $control = OrganizationControl::class;
}
