<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final readonly class StudentRequestDto
{
    public function __construct(
        public ?string $name = null,
        public ?string $gender = null,
        public ?string $birthDate = null,
        public ?string $height = null,
        public ?string $weight = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: isset($data['name']) ? (string) $data['name'] : null,
            gender: isset($data['gender']) ? (string) $data['gender'] : null,
            birthDate: isset($data['birth_date']) ? (string) $data['birth_date'] : null,
            height: isset($data['height']) ? (string) $data['height'] : null,
            weight: isset($data['weight']) ? (string) $data['weight'] : null,
        );
    }
}
