<?php

namespace Technical\Oidc\Models;

use Functional\Catalog\Models\Application;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Technical\Oidc\Database\Factories\SsoSessionFactory;

#[Fillable(['user_id', 'laravel_session_id', 'ip_address', 'user_agent', 'started_at', 'last_seen_at', 'expires_at'])]
#[UseFactory(SsoSessionFactory::class)]
class SsoSession extends Model
{
    use HasFactory;
    use HasUuids;

    public const SESSION_KEY = 'sso_session_id';

    public $timestamps = false;

    public function isAlive(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }

    public function revoke(): static
    {
        $this->forceFill(['revoked_at' => now()])->save();

        return $this;
    }

    public function touchLastSeen(): static
    {
        $this->forceFill(['last_seen_at' => now()])->save();

        return $this;
    }

    public function admit(Application $application): SsoSessionParticipant
    {
        return $this->participants()->firstOrCreate(
            ['application_id' => $application->getKey()],
            ['first_seen_at' => now()],
        );
    }

    #[Scope]
    protected function alive(Builder $query): void
    {
        $query->whereNull('revoked_at')->where('expires_at', '>', now());
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<SsoSessionParticipant, $this> */
    public function participants(): HasMany
    {
        return $this->hasMany(SsoSessionParticipant::class);
    }

    /** @return BelongsToMany<Application, $this> */
    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(
            Application::class,
            'sso_session_participants',
            'sso_session_id',
            'application_id',
        );
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
