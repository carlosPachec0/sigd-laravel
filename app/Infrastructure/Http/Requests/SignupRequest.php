<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Validation\Rules\Password;

final class SignupRequest extends BaseFormRequest
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
            'password' => ['required', 'confirmed', Password::default()],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid email address.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'name.required' => 'Name is required.',
            'name.max' => 'Name must not exceed 255 characters.',
        ];
    }
}
