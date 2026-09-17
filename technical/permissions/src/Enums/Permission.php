<?php

namespace Technical\Permissions\Enums;

enum Permission: string
{
    case DeclareOrganization = 'organizations.declare';
    case SuspendOrganization = 'organizations.suspend';
    case AttachLicense = 'licenses.attach';
    case InviteUser = 'users.invite';
    case GrantApplicationAccess = 'accesses.grant';
    case DeclareApplication = 'applications.declare';
    case PublishApplication = 'applications.publish';
    case RetireApplication = 'applications.retire';
    case RotateClientSecret = 'clients.rotate-secret';
    case ReadSecurityEvents = 'security-events.read';

    /** @return list<string> */
    public static function names(): array
    {
        return array_map(fn (self $permission): string => $permission->value, self::cases());
    }
}
