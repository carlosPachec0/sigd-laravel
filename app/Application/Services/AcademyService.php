<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\DTOs\AcademyRequestDto;
use App\Application\DTOs\AcademyResponseDto;
use App\Domain\Contracts\Repositories\AcademyRepositoryInterface;
use App\Domain\Entities\Academy;
use App\Domain\Entities\User;
use App\Domain\Exceptions\AcademyNotFoundException;

final class AcademyService
{
    public function __construct(
        private readonly AcademyRepositoryInterface $academyRepository,
    ) {}

    /**
     * @return array<AcademyResponseDto>
     */
    public function index(User $user): array
    {
        return $this->academyRepository
            ->getForUserId((string) $user->id)
            ->map(fn (Academy $academy) => AcademyResponseDto::fromAcademy($academy))
            ->all();
    }

    public function show(User $user, string $id): AcademyResponseDto
    {
        $academy = $this->findOwnedAcademy($user, $id);

        return AcademyResponseDto::fromAcademy($academy);
    }

    public function store(User $user, AcademyRequestDto $dto): AcademyResponseDto
    {
        $academy = $this->academyRepository->create([
            'user_id' => (string) $user->id,
            'name' => $dto->name,
            'discipline' => $dto->discipline,
            'registration_fee' => $dto->registrationFee,
            'monthly_fee' => $dto->monthlyFee,
            'class_fee' => $dto->classFee,
        ]);

        return AcademyResponseDto::fromAcademy($academy);
    }

    public function update(User $user, string $id, AcademyRequestDto $dto): AcademyResponseDto
    {
        $academy = $this->findOwnedAcademy($user, $id);

        $updated = $this->academyRepository->update($academy, [
            'name' => $dto->name,
            'discipline' => $dto->discipline,
            'registration_fee' => $dto->registrationFee,
            'monthly_fee' => $dto->monthlyFee,
            'class_fee' => $dto->classFee,
        ]);

        return AcademyResponseDto::fromAcademy($updated);
    }

    public function destroy(User $user, string $id): void
    {
        $academy = $this->findOwnedAcademy($user, $id);

        $this->academyRepository->delete($academy);
    }

    private function findOwnedAcademy(User $user, string $id): Academy
    {
        $academy = $this->academyRepository->findByIdForUser($id, (string) $user->id);

        if ($academy === null) {
            throw new AcademyNotFoundException;
        }

        return $academy;
    }
}
