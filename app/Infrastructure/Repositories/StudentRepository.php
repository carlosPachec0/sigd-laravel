<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\Repositories\StudentRepositoryInterface;
use App\Domain\Entities\Student;
use Illuminate\Support\Collection;

final class StudentRepository implements StudentRepositoryInterface
{
    public function findByIdForAcademy(string $id, string $academyId): ?Student
    {
        return Student::where('id', $id)
            ->where('academy_id', $academyId)
            ->first();
    }

    public function getForAcademyId(string $academyId): Collection
    {
        return Student::where('academy_id', $academyId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(array $data): Student
    {
        return Student::create($data);
    }

    public function update(Student $student, array $data): Student
    {
        $student->forceFill($data);
        $student->save();

        return $student->fresh();
    }

    public function delete(Student $student): void
    {
        $student->delete();
    }
}
