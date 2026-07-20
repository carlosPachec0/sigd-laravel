<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final readonly class SignupRequestDto
{
    public function __construct(
        public string $email,
        public string $password,
        public string $firstName,
        public string $lastName,
        public string $role,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password'],
            firstName: $data['first_name'],
            lastName: $data['last_name'],
            role: $data['role'],
        );
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'role' => $this->role,
        ];
    }
}
