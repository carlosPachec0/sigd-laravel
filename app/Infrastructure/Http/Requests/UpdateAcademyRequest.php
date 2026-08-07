<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

final class UpdateAcademyRequest extends BaseFormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'discipline' => ['sometimes', 'required', 'string', 'max:100'],
            'registration_fee' => ['sometimes', 'required', 'numeric', 'min:0'],
            'monthly_fee' => ['sometimes', 'required', 'numeric', 'min:0'],
            'class_fee' => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }
}
