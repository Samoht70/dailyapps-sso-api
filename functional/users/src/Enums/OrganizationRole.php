<?php

namespace Functional\Users\Enums;

enum OrganizationRole: string
{
    case Member = 'member';
    case Admin = 'admin';

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}
