<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final readonly class AssistanceRequestDto
{
    public function __construct(
        public ?string $date = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            date: isset($data['date']) ? (string) $data['date'] : null,
        );
    }
}
