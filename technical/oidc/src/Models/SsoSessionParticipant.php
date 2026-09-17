<?php

namespace Technical\Oidc\Models;

use Functional\Catalog\Models\Application;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Technical\Oidc\Database\Factories\SsoSessionParticipantFactory;

#[Fillable(['sso_session_id', 'application_id', 'first_seen_at'])]
#[UseFactory(SsoSessionParticipantFactory::class)]
class SsoSessionParticipant extends Model
{
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    public function awaitsLogoutPush(): bool
    {
        return $this->logout_pushed_at === null;
    }

    public function markLogoutPushed(): static
    {
        $this->forceFill(['logout_pushed_at' => now()])->save();

        return $this;
    }

    /** @return BelongsTo<SsoSession, $this> */
    public function ssoSession(): BelongsTo
    {
        return $this->belongsTo(SsoSession::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'logout_pushed_at' => 'datetime',
        ];
    }
}
