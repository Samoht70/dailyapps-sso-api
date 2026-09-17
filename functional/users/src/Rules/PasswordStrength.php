<?php

namespace Functional\Users\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class PasswordStrength implements ValidationRule
{
    public const MINIMUM_LENGTH = 12;

    /**
     * Each unmet requirement is reported on its own, so the screen can tell the
     * user what is missing rather than that the password is merely "too weak".
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $validator = Validator::make(
            [$attribute => $value],
            [$attribute => self::policy()],
        );

        foreach ($validator->errors()->get($attribute) as $reason) {
            $fail($reason);
        }
    }

    public static function policy(): Password
    {
        return Password::min(self::MINIMUM_LENGTH)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols();
    }
}
