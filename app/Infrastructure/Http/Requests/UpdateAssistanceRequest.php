<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

final class UpdateAssistanceRequest extends BaseFormRequest
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
            'date' => ['sometimes', 'required', 'date'],
        ];
    }
}
