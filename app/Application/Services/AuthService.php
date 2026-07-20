<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\DTOs\LoginRequestDto;
use App\Application\DTOs\LoginResponseDto;
use App\Application\DTOs\SignupRequestDto;
use App\Application\DTOs\SignupResponseDto;
use App\Domain\Contracts\Repositories\UserRepositoryInterface;
use App\Domain\Exceptions\InvalidCredentialsException;
use App\Domain\Exceptions\UserAlreadyExistsException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

final class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function signup(SignupRequestDto $dto): SignupResponseDto
    {
        $existingUser = $this->userRepository->findByEmail($dto->email);

        if ($existingUser !== null) {
            throw new UserAlreadyExistsException($dto->email);
        }

        $user = $this->userRepository->create([
            'email' => $dto->email,
            'password' => $dto->password,
            'first_name' => $dto->firstName,
            'last_name' => $dto->lastName,
            'role' => $dto->role,
        ]);

        Auth::guard('web')->login($user);

        return new SignupResponseDto(
            id: $user->id,
            email: $user->email,
            firstName: $user->first_name,
            lastName: $user->last_name,
            role: $user->role,
        );
    }

    public function login(LoginRequestDto $dto): LoginResponseDto
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if ($user === null || !Hash::check($dto->password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        Auth::guard('web')->login($user);

        return new LoginResponseDto(
            id: $user->id,
            email: $user->email,
            firstName: $user->first_name,
            lastName: $user->last_name,
            role: $user->role,
        );
    }
}
