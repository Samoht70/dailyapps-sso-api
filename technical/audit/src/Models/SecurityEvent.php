<?php

namespace Technical\Audit\Models;

use Functional\Organizations\Models\Organization;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Technical\Audit\Database\Factories\SecurityEventFactory;
use Technical\Audit\Enums\SecurityEventType;

#[Fillable(['type', 'actor_id', 'organization_id', 'subject_type', 'subject_id', 'ip_address', 'user_agent', 'payload'])]
#[UseFactory(SecurityEventFactory::class)]
class SecurityEvent extends Model
{
    use HasFactory;
    use HasUuids;
    use Prunable;

    public const UPDATED_AT = null;

    public const RETENTION_MONTHS = 12;

    /** @return Builder<$this> */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<=', now()->subMonths(self::RETENTION_MONTHS));
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => SecurityEventType::class,
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
