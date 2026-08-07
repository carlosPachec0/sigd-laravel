<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final readonly class AcademyRequestDto
{
    public function __construct(
        public string $name,
        public string $discipline,
        public string $registrationFee,
        public string $monthlyFee,
        public string $classFee,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            discipline: (string) $data['discipline'],
            registrationFee: (string) $data['registration_fee'],
            monthlyFee: (string) $data['monthly_fee'],
            classFee: (string) $data['class_fee'],
        );
    }
}
