<?php

namespace Functional\Users\Models;

use Functional\Licensing\Models\ApplicationAccess;
use Functional\Organizations\Models\Organization;
use Functional\Users\Database\Factories\UserFactory;
use Functional\Users\Enums\OrganizationRole;
use Functional\Users\Enums\UserStatus;
use Functional\Users\Models\Concerns\HasAccountState;
use Functional\Users\Notifications\PasswordResetRequested;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Technical\Oidc\Models\SsoSession;

#[Fillable(['organization_id', 'name', 'email', 'password', 'organization_role'])]
#[Hidden(['password', 'remember_token'])]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    use HasAccountState;
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable;

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
