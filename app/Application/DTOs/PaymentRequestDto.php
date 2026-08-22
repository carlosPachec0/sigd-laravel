<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final readonly class PaymentRequestDto
{
    public function __construct(
        public ?string $subject = null,
        public ?string $amount = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            subject: isset($data['subject']) ? (string) $data['subject'] : null,
            amount: isset($data['amount']) ? (string) $data['amount'] : null,
        );
    }
}
