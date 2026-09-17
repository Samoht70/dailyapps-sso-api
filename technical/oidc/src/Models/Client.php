<?php

namespace Technical\Oidc\Models;

use Functional\Catalog\Models\Application;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Passport\Client as PassportClient;
use Laravel\Passport\Scope;

class Client extends PassportClient
{
    /**
     * Every application of the ecosystem is first-party, so the consent screen
     * would ask the user to approve DailyApps to DailyApps.
     *
     * @param  Scope[]  $scopes
     */
    public function skipsAuthorization(Authenticatable $user, array $scopes): bool
    {
        return $this->firstParty();
    }

    /** @return HasOne<Application, $this> */
    public function application(): HasOne
    {
        return $this->hasOne(Application::class, 'oauth_client_id');
    }
}
