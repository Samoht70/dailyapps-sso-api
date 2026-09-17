<?php

namespace Functional\Organizations\Enums;

enum OrganizationStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function canTransitionTo(self $target): bool
    {
        return $this !== $target;
    }
}
