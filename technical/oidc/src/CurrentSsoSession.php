<?php

namespace Technical\Oidc;

class CurrentSsoSession
{
    private ?string $id = null;

    /**
     * Holds, for the duration of one token request, the SSO session the grant
     * read out of the authorization code or the refreshed token — the token
     * endpoint has no browser session to ask.
     */
    public function remember(?string $id): void
    {
        $this->id = $id;
    }

    public function id(): ?string
    {
        return $this->id;
    }
}
