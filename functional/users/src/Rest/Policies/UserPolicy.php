<?php

namespace Functional\Users\Rest\Policies;

use Functional\Users\Rest\Controls\UserControl;
use Lomkit\Access\Policies\ControlledPolicy;

class UserPolicy extends ControlledPolicy
{
    protected string $control = UserControl::class;
}
