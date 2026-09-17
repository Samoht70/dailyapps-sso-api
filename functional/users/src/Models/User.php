<?php

namespace Functional\Users\Models;

use Functional\Licensing\Models\ApplicationAccess;
use Functional\Organizations\Models\Organization;
use Functional\Users\Database\Factories\UserFactory;
use Functional\Users\Enums\OrganizationRole;
use Functional\Users\Enums\UserStatus;
use Functional\Users\Models\Concerns\HasAccountState;
use Functional\Users\Notifications\PasswordResetRequested;
use Functional\Users\Rest\Policies\UserPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Lomkit\Access\Controls\HasControl;
use Spatie\Permission\Traits\HasRoles;
use Technical\Oidc\Models\SsoSession;

#[Fillable(['organization_id', 'name', 'email', 'password', 'organization_role'])]
#[Hidden(['password', 'remember_token'])]
#[UsePolicy(UserPolicy::class)]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    use HasAccountState;
    use HasApiTokens, HasControl, HasFactory, HasRoles, HasUuids, Notifiable;

    /**
     * Permissions govern the person, not the channel they came in through. Left
     * to itself, spatie picks a guard from the auth config and would look for
     * `api` permissions that were never declared.
     */
    protected string $guard_name = 'web';

    /** @var array<string, string> */
    protected $attributes = [
        'status' => UserStatus::Invited->value,
        'organization_role' => OrganizationRole::Member->value,
    ];

    public function isOrganizationAdmin(): bool
    {
        return $this->organization_role->isAdmin();
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new PasswordResetRequested($token));
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return HasMany<ApplicationAccess, $this> */
    public function applicationAccesses(): HasMany
    {
        return $this->hasMany(ApplicationAccess::class);
    }

    /** @return HasMany<SsoSession, $this> */
    public function ssoSessions(): HasMany
    {
        return $this->hasMany(SsoSession::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_authenticated_at' => 'datetime',
            'organization_role' => OrganizationRole::class,
            'password' => 'hashed',
        ];
    }
}
