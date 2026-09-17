<?php

namespace Functional\Users\Http\Requests;

use Functional\Users\Rules\PasswordStrength;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password:api'],
            'password' => ['required', 'string', 'confirmed', new PasswordStrength],
        ];
    }
}
