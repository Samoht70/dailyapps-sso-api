<?php

namespace Functional\Users\Models;

use Functional\Organizations\Models\Organization;
use Functional\Users\Database\Factories\InvitationFactory;
use Functional\Users\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

#[Fillable(['email', 'organization_id', 'organization_role', 'invited_by_id', 'token_hash', 'expires_at'])]
#[UseFactory(InvitationFactory::class)]
class Invitation extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * The plain token exists only in the message sent to the person; the row
     * keeps a hash, so a leaked database grants nobody an account.
     */
    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function freshToken(): string
    {
        return Str::random(48);
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }

    public function accept(string $password): User
    {
        $account = User::query()->where('email', $this->email)->sole();

        $account->forceFill([
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ])->save();

        $account->state()->activate();

        $this->forceFill(['accepted_at' => now()])->save();

        return $account->fresh();
    }

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->whereNull('accepted_at')->where('expires_at', '>', now());
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<User, $this> */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'organization_role' => OrganizationRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }
}
