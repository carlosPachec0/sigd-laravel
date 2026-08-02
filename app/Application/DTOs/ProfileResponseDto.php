<?php

declare(strict_types=1);

namespace App\Application\DTOs;

use App\Domain\Entities\User;

final readonly class ProfileResponseDto
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
        public ?string $emailVerifiedAt = null,
    ) {}

    public static function fromUser(User $user): self
    {
        return new self(
            id: (string) $user->id,
            email: $user->email,
            name: $user->name,
            emailVerifiedAt: $user->email_verified_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'email_verified_at' => $this->emailVerifiedAt,
        ];
    }
}
