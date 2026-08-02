<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

use Illuminate\Validation\Rule;

final class UpdateProfileRequest extends BaseFormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email:rfc',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
        ];
    }
}
