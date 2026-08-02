<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Validation\Rules\Password;

final class ChangePasswordRequest extends BaseFormRequest
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
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'confirmed', 'different:current_password', Password::default()],
        ];
    }
}
