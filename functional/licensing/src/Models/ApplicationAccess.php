<?php

namespace Functional\Licensing\Models;

use Functional\Catalog\Models\Application;
use Functional\Catalog\Models\ApplicationRole;
use Functional\Licensing\Database\Factories\ApplicationAccessFactory;
use Functional\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['user_id', 'application_id', 'granted_by_id', 'granted_at'])]
#[UseFactory(ApplicationAccessFactory::class)]
class ApplicationAccess extends Model
{
    use HasFactory;
    use HasUuids;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /** @return BelongsTo<User, $this> */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_id');
    }

    /** @return BelongsToMany<ApplicationRole, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            ApplicationRole::class,
            'application_access_role',
            'application_access_id',
            'application_role_id',
        );
    }
}
