<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

final class ForgotPasswordRequest extends BaseFormRequest
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
            // Deliberately no `exists:users,email` — that would leak whether
            // an account exists for this email via the validation error.
            'email' => ['required', 'email:rfc'],
        ];
    }
}
