<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final readonly class ResetPasswordRequestDto
{
    public function __construct(
        public string $email,
        public string $token,
        public string $password,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            token: $data['token'],
            password: $data['password'],
        );
    }
}
