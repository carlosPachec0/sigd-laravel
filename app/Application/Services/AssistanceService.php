<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\DTOs\AssistanceRequestDto;
use App\Application\DTOs\AssistanceResponseDto;
use App\Domain\Contracts\Repositories\AcademyRepositoryInterface;
use App\Domain\Contracts\Repositories\AssistanceRepositoryInterface;
use App\Domain\Contracts\Repositories\StudentRepositoryInterface;
use App\Domain\Entities\Academy;
use App\Domain\Entities\Assistance;
use App\Domain\Entities\Student;
use App\Domain\Entities\User;
use App\Domain\Exceptions\AcademyNotFoundException;
use App\Domain\Exceptions\AssistanceAlreadyExistsException;
use App\Domain\Exceptions\AssistanceNotFoundException;
use App\Domain\Exceptions\StudentNotFoundException;

final class AssistanceService
{
    public function __construct(
        private readonly AcademyRepositoryInterface $academyRepository,
        private readonly StudentRepositoryInterface $studentRepository,
        private readonly AssistanceRepositoryInterface $assistanceRepository,
    ) {}

    /**
     * @return array<AssistanceResponseDto>
     */
    public function index(User $user, string $academyId, string $studentId): array
    {
        $this->findOwnedStudent($user, $academyId, $studentId);

        return $this->assistanceRepository
            ->getForStudentId($studentId)
            ->map(fn (Assistance $assistance) => AssistanceResponseDto::fromAssistance($assistance))
            ->all();
    }

    public function show(User $user, string $academyId, string $studentId, string $assistanceId): AssistanceResponseDto
    {
        $assistance = $this->findOwnedAssistance($user, $academyId, $studentId, $assistanceId);

        return AssistanceResponseDto::fromAssistance($assistance);
    }

    public function store(User $user, string $academyId, string $studentId, AssistanceRequestDto $dto): AssistanceResponseDto
    {
        $student = $this->findOwnedStudent($user, $academyId, $studentId);

        $this->ensureDateIsAvailable($studentId, $dto->date);

        $assistance = $this->assistanceRepository->create([
            'student_id' => $student->id,
            'date' => $dto->date,
        ]);

        return AssistanceResponseDto::fromAssistance($assistance);
    }

    public function update(User $user, string $academyId, string $studentId, string $assistanceId, AssistanceRequestDto $dto): AssistanceResponseDto
    {
        $assistance = $this->findOwnedAssistance($user, $academyId, $studentId, $assistanceId);

        if ($dto->date !== null && $dto->date !== $assistance->date->toDateString()) {
            $this->ensureDateIsAvailable($studentId, $dto->date, excludeId: (string) $assistance->id);
        }

        $updated = $this->assistanceRepository->update($assistance, [
            'date' => $dto->date ?? $assistance->date,
        ]);

        return AssistanceResponseDto::fromAssistance($updated);
    }

    public function destroy(User $user, string $academyId, string $studentId, string $assistanceId): void
    {
        $assistance = $this->findOwnedAssistance($user, $academyId, $studentId, $assistanceId);

        $this->assistanceRepository->delete($assistance);
    }

    private function ensureDateIsAvailable(string $studentId, string $date, ?string $excludeId = null): void
    {
        $existing = $this->assistanceRepository->findByStudentAndDate($studentId, $date, $excludeId);

        if ($existing !== null) {
            throw new AssistanceAlreadyExistsException;
        }
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

    private function findOwnedAssistance(User $user, string $academyId, string $studentId, string $assistanceId): Assistance
    {
        $this->findOwnedStudent($user, $academyId, $studentId);

        $assistance = $this->assistanceRepository->findByIdForStudent($assistanceId, $studentId);

        if ($assistance === null) {
            throw new AssistanceNotFoundException;
        }

        return $assistance;
    }
}
