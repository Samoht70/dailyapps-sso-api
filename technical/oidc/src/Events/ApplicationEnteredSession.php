<?php

namespace Technical\Oidc\Events;

use Functional\Catalog\Models\Application;
use Illuminate\Foundation\Events\Dispatchable;
use Technical\Oidc\Models\SsoSession;

class ApplicationEnteredSession
{
    use Dispatchable;

    public function __construct(
        public readonly SsoSession $session,
        public readonly Application $application,
    ) {}
}
