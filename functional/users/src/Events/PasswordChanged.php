<?php

namespace Functional\Users\Events;

use Functional\Users\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class PasswordChanged
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly ?string $keptSsoSessionId = null,
    ) {}
}
