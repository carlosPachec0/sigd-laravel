<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final readonly class UpdateProfileRequestDto
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
        );
    }
}
