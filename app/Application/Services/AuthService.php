<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\DTOs\ForgotPasswordRequestDto;
use App\Application\DTOs\LoginRequestDto;
use App\Application\DTOs\LoginResponseDto;
use App\Application\DTOs\ResetPasswordRequestDto;
use App\Application\DTOs\SignupRequestDto;
use App\Application\DTOs\SignupResponseDto;
use App\Domain\Contracts\Repositories\UserRepositoryInterface;
use App\Domain\Entities\User;
use App\Domain\Exceptions\InvalidCredentialsException;
use App\Domain\Exceptions\InvalidPasswordResetTokenException;
use App\Domain\Exceptions\InvalidVerificationLinkException;
use App\Domain\Exceptions\UserAlreadyExistsException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

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
            'name' => $dto->name,
        ]);

        $user->sendEmailVerificationNotification();

        return new SignupResponseDto(
            id: (string) $user->id,
            email: $user->email,
            name: $user->name,
            token: $user->createToken('api-token')->plainTextToken,
            emailVerifiedAt: $user->email_verified_at?->toISOString(),
        );
    }

    public function login(LoginRequestDto $dto): LoginResponseDto
    {
        $user = $this->userRepository->findByEmail($dto->email);

        if ($user === null || ! Hash::check($dto->password, $user->password)) {
            throw new InvalidCredentialsException;
        }

        return new LoginResponseDto(
            id: (string) $user->id,
            email: $user->email,
            name: $user->name,
            token: $user->createToken('api-token')->plainTextToken,
            emailVerifiedAt: $user->email_verified_at?->toISOString(),
        );
    }

    public function logout(PersonalAccessToken $currentToken): void
    {
        $currentToken->delete();
    }

    public function forgotPassword(ForgotPasswordRequestDto $dto): void
    {
        // Return value is intentionally ignored — the controller always
        // responds with the same generic message regardless of outcome,
        // to avoid leaking whether an account exists for this email.
        Password::sendResetLink(['email' => $dto->email]);
    }

    public function resetPassword(ResetPasswordRequestDto $dto): void
    {
        $status = Password::reset(
            [
                'email' => $dto->email,
                'password' => $dto->password,
                'token' => $dto->token,
            ],
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password])
                    ->setRememberToken(Str::random(60));
                $user->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new InvalidPasswordResetTokenException;
        }
    }

    public function verifyEmail(string $id, string $hash): void
    {
        $user = $this->userRepository->findById($id);

        if ($user === null || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            throw new InvalidVerificationLinkException;
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }
    }

    public function resendVerificationEmail(User $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }
    }
}
