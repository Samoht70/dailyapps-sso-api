<?php

namespace Functional\Licensing\Rest\Policies;

use Functional\Licensing\Rest\Controls\LicenseControl;
use Lomkit\Access\Policies\ControlledPolicy;

class LicensePolicy extends ControlledPolicy
{
    protected string $control = LicenseControl::class;
}
