<?php

namespace Technical\Oidc\Listeners;

use Functional\Users\Events\PasswordChanged;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Technical\Audit\Actions\RecordSecurityEvent;
use Technical\Audit\Enums\SecurityEventType;

class RecordAuthenticationEvents
{
    public function __construct(private readonly RecordSecurityEvent $record) {}

    public function handleLogin(Login $event): void
    {
        ($this->record)(SecurityEventType::AuthenticationSucceeded, actor: $event->user);
    }

    public function handleFailure(Failed $event): void
    {
        ($this->record)(
            SecurityEventType::AuthenticationFailed,
            actor: $event->user,
            payload: ['email' => $event->credentials['email'] ?? null],
        );
    }

    public function handlePasswordChanged(PasswordChanged $event): void
    {
        ($this->record)(SecurityEventType::PasswordChanged, actor: $event->user, subject: $event->user);
    }

    public function handleResetLinkSent(PasswordResetLinkSent $event): void
    {
        ($this->record)(SecurityEventType::PasswordResetRequested, actor: $event->user, subject: $event->user);
    }
}
