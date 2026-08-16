<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\DTOs\StudentRequestDto;
use App\Application\DTOs\StudentResponseDto;
use App\Domain\Contracts\Repositories\AcademyRepositoryInterface;
use App\Domain\Contracts\Repositories\StudentRepositoryInterface;
use App\Domain\Entities\Academy;
use App\Domain\Entities\Student;
use App\Domain\Entities\User;
use App\Domain\Exceptions\AcademyNotFoundException;
use App\Domain\Exceptions\StudentNotFoundException;

final class StudentService
{
    public function __construct(
        private readonly AcademyRepositoryInterface $academyRepository,
        private readonly StudentRepositoryInterface $studentRepository,
    ) {}

    /**
     * @return array<StudentResponseDto>
     */
    public function index(User $user, string $academyId): array
    {
        $this->findOwnedAcademy($user, $academyId);

        return $this->studentRepository
            ->getForAcademyId($academyId)
            ->map(fn (Student $student) => StudentResponseDto::fromStudent($student))
            ->all();
    }

    public function show(User $user, string $academyId, string $studentId): StudentResponseDto
    {
        $student = $this->findOwnedStudent($user, $academyId, $studentId);

        return StudentResponseDto::fromStudent($student);
    }

    public function store(User $user, string $academyId, StudentRequestDto $dto): StudentResponseDto
    {
        $academy = $this->findOwnedAcademy($user, $academyId);

        $student = $this->studentRepository->create([
            'academy_id' => $academy->id,
            'name' => $dto->name,
            'gender' => $dto->gender,
            'birth_date' => $dto->birthDate,
            'height' => $dto->height,
            'weight' => $dto->weight,
        ]);

        return StudentResponseDto::fromStudent($student);
    }

    public function update(User $user, string $academyId, string $studentId, StudentRequestDto $dto): StudentResponseDto
    {
        $student = $this->findOwnedStudent($user, $academyId, $studentId);

        $updated = $this->studentRepository->update($student, [
            'name' => $dto->name ?? $student->name,
            'gender' => $dto->gender ?? $student->gender,
            'birth_date' => $dto->birthDate ?? $student->birth_date?->toDateString(),
            'height' => $dto->height ?? $student->height,
            'weight' => $dto->weight ?? $student->weight,
        ]);

        return StudentResponseDto::fromStudent($updated);
    }

    public function destroy(User $user, string $academyId, string $studentId): void
    {
        $student = $this->findOwnedStudent($user, $academyId, $studentId);

        $this->studentRepository->delete($student);
    }

    private function findOwnedAcademy(User $user, string $academyId): Academy
    {
        $academy = $this->academyRepository->findByIdForUser($academyId, (string) $user->id);

        if ($academy === null) {
            throw new AcademyNotFoundException;
        }

        return $academy;
    }

    private function findOwnedStudent(User $user, string $academyId, string $studentId): Student
    {
        $this->findOwnedAcademy($user, $academyId);

        $student = $this->studentRepository->findByIdForAcademy($studentId, $academyId);

        if ($student === null) {
            throw new StudentNotFoundException;
        }

        return $student;
    }
}
