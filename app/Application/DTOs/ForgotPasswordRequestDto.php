<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final readonly class ForgotPasswordRequestDto
{
    public function __construct(
        public string $email,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
        );
    }
}
