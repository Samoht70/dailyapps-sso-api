<?php

namespace Functional\Users\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class UserInvited extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject(Lang::get('oidc::mail.invitation.subject'))
            ->line(Lang::get('oidc::mail.invitation.line'))
            ->action(
                Lang::get('oidc::mail.invitation.action'),
                url(route('invitations.accept', ['token' => $this->token], absolute: false)),
            )
            ->line(Lang::get('oidc::mail.invitation.expires', [
                'days' => config('oidc.invitation.lifetime_days'),
            ]));
    }
}
