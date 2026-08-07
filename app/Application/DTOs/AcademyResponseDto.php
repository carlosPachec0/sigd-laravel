<?php

declare(strict_types=1);

namespace App\Application\DTOs;

use App\Domain\Entities\Academy;

final readonly class AcademyResponseDto
{
    public function __construct(
        public string $id,
        public string $userId,
        public string $name,
        public string $discipline,
        public string $registrationFee,
        public string $monthlyFee,
        public string $classFee,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}

    public static function fromAcademy(Academy $academy): self
    {
        return new self(
            id: (string) $academy->id,
            userId: (string) $academy->user_id,
            name: $academy->name,
            discipline: $academy->discipline,
            registrationFee: $academy->registration_fee,
            monthlyFee: $academy->monthly_fee,
            classFee: $academy->class_fee,
            createdAt: $academy->created_at?->toISOString(),
            updatedAt: $academy->updated_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'name' => $this->name,
            'discipline' => $this->discipline,
            'registration_fee' => $this->registrationFee,
            'monthly_fee' => $this->monthlyFee,
            'class_fee' => $this->classFee,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
