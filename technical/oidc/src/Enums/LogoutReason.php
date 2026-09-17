<?php

namespace Technical\Oidc\Enums;

enum LogoutReason: string
{
    case Logout = 'logout';
    case UserDisabled = 'user_disabled';
    case OrganizationSuspended = 'organization_suspended';
    case LicenseRevoked = 'license_revoked';
    case PasswordChanged = 'password_changed';
}
