<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Requests;

final class StorePaymentRequest extends BaseFormRequest
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
            'subject' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
