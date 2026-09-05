<?php

declare(strict_types=1);

namespace App\Application\DTOs;

use App\Domain\Entities\Assistance;

final readonly class AssistanceResponseDto
{
    public function __construct(
        public string $id,
        public string $studentId,
        public string $date,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}

    public static function fromAssistance(Assistance $assistance): self
    {
        return new self(
            id: (string) $assistance->id,
            studentId: (string) $assistance->student_id,
            date: $assistance->date?->toDateString() ?? '',
            createdAt: $assistance->created_at?->toISOString(),
            updatedAt: $assistance->updated_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->studentId,
            'date' => $this->date,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
