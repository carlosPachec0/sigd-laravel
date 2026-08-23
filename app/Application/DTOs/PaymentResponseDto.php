<?php

declare(strict_types=1);

namespace App\Application\DTOs;

use App\Domain\Entities\Payment;

final readonly class PaymentResponseDto
{
    public function __construct(
        public string $id,
        public string $studentId,
        public string $subject,
        public string $amount,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}

    public static function fromPayment(Payment $payment): self
    {
        return new self(
            id: (string) $payment->id,
            studentId: (string) $payment->student_id,
            subject: $payment->subject,
            amount: $payment->amount,
            createdAt: $payment->created_at?->toISOString(),
            updatedAt: $payment->updated_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->studentId,
            'subject' => $this->subject,
            'amount' => $this->amount,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
