<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final readonly class SignupRequestDto
{
    public function __construct(
        public string $email,
        public string $password,
        public string $name,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password'],
            name: $data['name'],
        );
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'name' => $this->name,
        ];
    }
}
