<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final readonly class LoginResponseDto
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
        public string $token,
        public ?string $emailVerifiedAt = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'token' => $this->token,
            'email_verified_at' => $this->emailVerifiedAt,
        ];
    }
}
