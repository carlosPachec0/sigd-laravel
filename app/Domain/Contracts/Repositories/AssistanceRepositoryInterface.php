<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

use App\Domain\Entities\Assistance;
use Illuminate\Support\Collection;

interface AssistanceRepositoryInterface
{
    public function findByIdForStudent(string $id, string $studentId): ?Assistance;

    public function getForStudentId(string $studentId): Collection;

    public function findByStudentAndDate(string $studentId, string $date, ?string $excludeId = null): ?Assistance;

    public function create(array $data): Assistance;

    public function update(Assistance $assistance, array $data): Assistance;

    public function delete(Assistance $assistance): void;
}
