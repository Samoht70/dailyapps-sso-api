<?php

namespace Functional\Users\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class PasswordResetRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = config('auth.passwords.users.expire');

        return (new MailMessage)
            ->subject(Lang::get('oidc::mail.password_reset.subject'))
            ->line(Lang::get('oidc::mail.password_reset.line'))
            ->action(Lang::get('oidc::mail.password_reset.action'), $this->resetUrl($notifiable))
            ->line(Lang::get('oidc::mail.password_reset.expires', ['minutes' => $minutes]))
            ->line(Lang::get('oidc::mail.password_reset.ignore'));
    }

    private function resetUrl(object $notifiable): string
    {
        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], absolute: false));
    }
}
