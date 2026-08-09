<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

final class StoreAcademyRequest extends BaseFormRequest
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
            'discipline' => ['required', 'string', 'max:100'],
            'registration_fee' => ['required', 'numeric', 'min:0'],
            'monthly_fee' => ['required', 'numeric', 'min:0'],
            'class_fee' => ['required', 'numeric', 'min:0'],
        ];
    }
}
