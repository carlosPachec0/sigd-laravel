<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

final class UpdatePaymentRequest extends BaseFormRequest
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
            'subject' => ['sometimes', 'required', 'string', 'max:100'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }
}
