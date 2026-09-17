<?php

namespace Technical\Oidc\Enums;

enum RefusalReason: string
{
    case AccountUnavailable = 'account_unavailable';
    case NoLicense = 'no_license';
    case NoAccess = 'no_access';
    case Throttled = 'throttled';
}
