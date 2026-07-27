<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final readonly class SignupResponseDto
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
        ];
    }
}
