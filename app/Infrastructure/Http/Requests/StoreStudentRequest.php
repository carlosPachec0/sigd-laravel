<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

final class StoreStudentRequest extends BaseFormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'gender' => ['required', 'string', 'in:Male,Female'],
            'birth_date' => ['required', 'date'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
