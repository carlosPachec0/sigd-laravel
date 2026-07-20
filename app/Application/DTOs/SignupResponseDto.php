<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final readonly class SignupResponseDto
{
    public function __construct(
        public int $id,
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $role,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'role' => $this->role,
        ];
    }
}
