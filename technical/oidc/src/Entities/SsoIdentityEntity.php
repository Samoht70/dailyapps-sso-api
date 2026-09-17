<?php

namespace Technical\Oidc\Entities;

use Functional\Users\Models\User;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use OpenIDConnect\Claims\Traits\WithClaims;
use OpenIDConnect\Entities\Traits\WithCustomPermittedFor;
use OpenIDConnect\Interfaces\IdentityEntityInterface;
use Technical\Oidc\Actions\ResolveCallingClient;
use Technical\Oidc\ClaimsRegistry;
use Technical\Oidc\CurrentSsoSession;

class SsoIdentityEntity implements IdentityEntityInterface
{
    use EntityTrait;
    use WithClaims;
    use WithCustomPermittedFor;

    private ?User $account = null;

    public function setAccount(?User $account): void
    {
        $this->account = $account;
    }

    /**
     * @param  string[]  $scopes
     * @return array<string, mixed>
     */
    public function getClaims(array $scopes = []): array
    {
        if ($this->account === null) {
            return [];
        }

        $client = app(ResolveCallingClient::class)();

        $claims = [
            'sid' => app(CurrentSsoSession::class)->id(),
            'name' => $this->account->name,
            'email' => $this->account->email,
            'email_verified' => $this->account->email_verified_at !== null,
        ];

        if ($client === null) {
            return $claims;
        }

        return array_merge($claims, app(ClaimsRegistry::class)->claimsFor($this->account, $client));
    }
}
