<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final readonly class ChangePasswordRequestDto
{
    public function __construct(
        public string $currentPassword,
        public string $newPassword,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            currentPassword: $data['current_password'],
            newPassword: $data['new_password'],
        );
    }
}
