<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

use App\Domain\Entities\Student;
use Illuminate\Support\Collection;

interface StudentRepositoryInterface
{
    public function findByIdForAcademy(string $id, string $academyId): ?Student;

    public function getForAcademyId(string $academyId): Collection;

    public function create(array $data): Student;

    public function update(Student $student, array $data): Student;

    public function delete(Student $student): void;
}
