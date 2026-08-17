<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

final class UpdateStudentRequest extends BaseFormRequest
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
            'gender' => ['sometimes', 'required', 'string', 'in:Male,Female'],
            'birth_date' => ['sometimes', 'required', 'date'],
            'height' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'weight' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
