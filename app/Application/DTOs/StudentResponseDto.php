<?php

declare(strict_types=1);

namespace App\Application\DTOs;

use App\Domain\Entities\Student;

final readonly class StudentResponseDto
{
    public function __construct(
        public string $id,
        public string $academyId,
        public string $name,
        public string $gender,
        public string $birthDate,
        public ?string $height = null,
        public ?string $weight = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}

    public static function fromStudent(Student $student): self
    {
        return new self(
            id: (string) $student->id,
            academyId: (string) $student->academy_id,
            name: $student->name,
            gender: $student->gender,
            birthDate: $student->birth_date?->toDateString() ?? '',
            height: $student->height,
            weight: $student->weight,
            createdAt: $student->created_at?->toISOString(),
            updatedAt: $student->updated_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'academy_id' => $this->academyId,
            'name' => $this->name,
            'gender' => $this->gender,
            'birth_date' => $this->birthDate,
            'height' => $this->height,
            'weight' => $this->weight,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
