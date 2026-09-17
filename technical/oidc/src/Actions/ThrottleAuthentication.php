<?php

namespace Technical\Oidc\Actions;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Technical\Audit\Actions\RecordSecurityEvent;
use Technical\Audit\Enums\SecurityEventType;
use Technical\Oidc\Exceptions\AuthenticationThrottled;

class ThrottleAuthentication
{
    public const MAX_ATTEMPTS_PER_ACCOUNT = 5;

    public const MAX_ATTEMPTS_PER_ORIGIN = 20;

    public const DECAY_SECONDS = 300;

    public function __construct(private readonly RecordSecurityEvent $record) {}

    /**
     * Locks on the account and on the origin at once: the first stops a password
     * being guessed, the second stops a whole address book being swept from one
     * machine.
     *
     * @throws AuthenticationThrottled when either counter is exhausted
     */
    public function ensureIsNotLocked(string $email, string $origin): void
    {
        foreach ($this->limits($email, $origin) as $key => $maximum) {
            if (RateLimiter::tooManyAttempts($key, $maximum)) {
                $seconds = RateLimiter::availableIn($key);

                $this->record->__invoke(
                    SecurityEventType::AuthenticationThrottled,
                    payload: ['email' => $email, 'seconds_remaining' => $seconds],
                );

                throw AuthenticationThrottled::forAnother($seconds);
            }
        }
    }

    public function recordFailure(string $email, string $origin): void
    {
        foreach (array_keys($this->limits($email, $origin)) as $key) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
        }
    }

    public function forget(string $email, string $origin): void
    {
        foreach (array_keys($this->limits($email, $origin)) as $key) {
            RateLimiter::clear($key);
        }
    }

    /** @return array<string, int> */
    private function limits(string $email, string $origin): array
    {
        return [
            'authentication:account:'.Str::lower($email) => self::MAX_ATTEMPTS_PER_ACCOUNT,
            'authentication:origin:'.$origin => self::MAX_ATTEMPTS_PER_ORIGIN,
        ];
    }
}
