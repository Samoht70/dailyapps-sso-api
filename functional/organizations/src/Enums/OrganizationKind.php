<?php

namespace Functional\Organizations\Enums;

enum OrganizationKind: string
{
    case Operator = 'operator';
    case Client = 'client';

    public function isOperator(): bool
    {
        return $this === self::Operator;
    }

    public function canBeSuspended(): bool
    {
        return $this === self::Client;
    }
}
