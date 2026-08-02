<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Validation\Rules\Password;

final class ResetPasswordRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::default()],
        ];
    }
}
