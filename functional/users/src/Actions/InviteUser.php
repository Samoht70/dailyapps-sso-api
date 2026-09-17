<?php

namespace Functional\Users\Actions;

use Functional\Organizations\Models\Organization;
use Functional\Users\Enums\OrganizationRole;
use Functional\Users\Enums\UserStatus;
use Functional\Users\Exceptions\EmailAlreadyAttached;
use Functional\Users\Models\Invitation;
use Functional\Users\Models\User;
use Functional\Users\Notifications\UserInvited;
use Illuminate\Support\Facades\DB;
use Technical\Audit\Actions\RecordSecurityEvent;
use Technical\Audit\Enums\SecurityEventType;

class InviteUser
{
    public function __construct(private readonly RecordSecurityEvent $record) {}

    /**
     * An address already attached is refused outright: creating a second ghost
     * account or moving the person silently would both leave the ecosystem with
     * two answers to "who is this".
     *
     * @throws EmailAlreadyAttached
     */
    public function __invoke(
        Organization $organization,
        string $email,
        string $name,
        OrganizationRole $role = OrganizationRole::Member,
        ?User $invitedBy = null,
    ): Invitation {
        return DB::transaction(function () use ($organization, $email, $name, $role, $invitedBy): Invitation {
            $account = User::query()->where('email', $email)->first();

            if ($account !== null && $account->status !== UserStatus::Invited) {
                throw EmailAlreadyAttached::to($email);
            }

            if ($account !== null && ! $organization->is($account->organization)) {
                throw EmailAlreadyAttached::to($email);
            }

            $account ??= User::query()->create([
                'organization_id' => $organization->getKey(),
                'name' => $name,
                'email' => $email,
                'organization_role' => $role->value,
            ]);

            Invitation::query()
                ->where('email', $email)
                ->pending()
                ->update(['expires_at' => now()]);

            $token = Invitation::freshToken();

            $invitation = Invitation::query()->create([
                'email' => $email,
                'organization_id' => $organization->getKey(),
                'organization_role' => $role->value,
                'invited_by_id' => $invitedBy?->getKey(),
                'token_hash' => Invitation::hash($token),
                'expires_at' => now()->addDays(config('oidc.invitation.lifetime_days')),
            ]);

            $account->notify(new UserInvited($token));

            ($this->record)(
                SecurityEventType::InvitationSent,
                actor: $invitedBy,
                organization: $organization,
                subject: $invitation,
                payload: ['email' => $email],
            );

            return $invitation;
        });
    }
}
