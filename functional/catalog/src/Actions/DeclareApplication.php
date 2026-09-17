<?php

namespace Functional\Catalog\Actions;

use Functional\Catalog\Models\Application;
use Functional\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Technical\Oidc\Models\Client;

class DeclareApplication
{
    /**
     * Returns the application with its plain secret still attached: Passport
     * hashes what it stores, so this is the only moment the secret can be read.
     *
     * @param  list<string>  $redirectUris
     */
    public function __invoke(
        string $slug,
        string $name,
        string $homeUrl,
        array $redirectUris,
        ?string $backchannelLogoutUrl = null,
        ?User $declaredBy = null,
    ): Application {
        return DB::transaction(function () use ($slug, $name, $homeUrl, $redirectUris, $backchannelLogoutUrl): Application {
            $client = Client::query()->create([
                'name' => $name,
                'secret' => Str::random(40),
                'redirect_uris' => $redirectUris,
                'grant_types' => ['authorization_code', 'refresh_token'],
                'revoked' => false,
            ]);

            $application = Application::query()->create([
                'slug' => $slug,
                'name' => $name,
                'home_url' => $homeUrl,
                'backchannel_logout_url' => $backchannelLogoutUrl,
                'oauth_client_id' => $client->getKey(),
            ]);

            $application->setRelation('oauthClient', $client);

            return $application;
        });
    }
}
