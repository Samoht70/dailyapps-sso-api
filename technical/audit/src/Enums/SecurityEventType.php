<?php

namespace Technical\Audit\Enums;

enum SecurityEventType: string
{
    case AuthenticationSucceeded = 'authentication_succeeded';
    case AuthenticationFailed = 'authentication_failed';
    case AuthenticationThrottled = 'authentication_throttled';
    case SessionEnded = 'session_ended';
    case PasswordChanged = 'password_changed';
    case PasswordResetRequested = 'password_reset_requested';
    case InvitationSent = 'invitation_sent';
    case InvitationAccepted = 'invitation_accepted';
    case AccessGranted = 'access_granted';
    case AccessRevoked = 'access_revoked';
    case UserDisabled = 'user_disabled';
    case UserEnabled = 'user_enabled';
    case OrganizationSuspended = 'organization_suspended';
    case LicenseAttached = 'license_attached';
    case LicenseRevoked = 'license_revoked';
    case ApplicationPublished = 'application_published';
    case ApplicationRetired = 'application_retired';
    case ClientSecretRotated = 'client_secret_rotated';
    case LogoutPushFailed = 'logout_push_failed';
}
