<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\DTOs\ChangePasswordRequestDto;
use App\Application\DTOs\ProfileResponseDto;
use App\Application\DTOs\UpdateProfileRequestDto;
use App\Domain\Contracts\Repositories\UserRepositoryInterface;
use App\Domain\Entities\User;
use App\Domain\Exceptions\InvalidCurrentPasswordException;
use Illuminate\Support\Facades\Hash;

final class ProfileService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function getProfile(User $user): ProfileResponseDto
    {
        return ProfileResponseDto::fromUser($user);
    }

    public function updateProfile(User $user, UpdateProfileRequestDto $dto): ProfileResponseDto
    {
        $emailChanged = $dto->email !== $user->email;

        $data = [
            'name' => $dto->name,
            'email' => $dto->email,
        ];

        if ($emailChanged) {
            $data['email_verified_at'] = null;
        }

        $updated = $this->userRepository->update($user, $data);

        if ($emailChanged) {
            $updated->sendEmailVerificationNotification();
        }

        return ProfileResponseDto::fromUser($updated);
    }

    public function changePassword(User $user, ChangePasswordRequestDto $dto): void
    {
        if (! Hash::check($dto->currentPassword, $user->password)) {
            throw new InvalidCurrentPasswordException;
        }

        $this->userRepository->update($user, ['password' => $dto->newPassword]);

        $currentTokenId = $user->currentAccessToken()?->id;

        $user->tokens()
            ->when($currentTokenId, fn ($query) => $query->where('id', '!=', $currentTokenId))
            ->delete();
    }
}
