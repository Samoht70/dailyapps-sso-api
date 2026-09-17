<?php

namespace Functional\Catalog\Models;

use Functional\Catalog\Database\Factories\ApplicationFactory;
use Functional\Catalog\Enums\ApplicationStatus;
use Functional\Catalog\Models\Concerns\Publishable;
use Functional\Catalog\Rest\Policies\ApplicationPolicy;
use Functional\Licensing\Models\ApplicationAccess;
use Functional\Licensing\Models\License;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lomkit\Access\Controls\HasControl;
use Technical\Oidc\Models\Client;

#[Fillable(['slug', 'name', 'description', 'logo_url', 'home_url', 'backchannel_logout_url', 'oauth_client_id'])]
#[UsePolicy(ApplicationPolicy::class)]
#[UseFactory(ApplicationFactory::class)]
class Application extends Model
{
    use HasControl;
    use HasFactory;
    use HasUuids;
    use Publishable;

    /** @var array<string, string> */
    protected $attributes = [
        'status' => ApplicationStatus::Draft->value,
    ];

    public function receivesLogoutPush(): bool
    {
        return $this->backchannel_logout_url !== null;
    }

    /** @return HasMany<ApplicationRole, $this> */
    public function roles(): HasMany
    {
        return $this->hasMany(ApplicationRole::class);
    }

    /** @return HasMany<License, $this> */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    /** @return HasMany<ApplicationAccess, $this> */
    public function accesses(): HasMany
    {
        return $this->hasMany(ApplicationAccess::class);
    }

    /** @return BelongsTo<Client, $this> */
    public function oauthClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'oauth_client_id');
    }
}
