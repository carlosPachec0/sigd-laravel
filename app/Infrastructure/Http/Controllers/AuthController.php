<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\DTOs\ForgotPasswordRequestDto;
use App\Application\DTOs\LoginRequestDto;
use App\Application\DTOs\ResetPasswordRequestDto;
use App\Application\DTOs\SignupRequestDto;
use App\Application\Services\AuthService;
use App\Infrastructure\Http\Requests\ForgotPasswordRequest;
use App\Infrastructure\Http\Requests\LoginRequest;
use App\Infrastructure\Http\Requests\ResetPasswordRequest;
use App\Infrastructure\Http\Requests\SignupRequest;
use App\Infrastructure\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

final class AuthController
{
    use ApiResponse;

    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function signup(SignupRequest $request): JsonResponse
    {
        $dto = SignupRequestDto::fromArray($request->validated());

        $response = $this->authService->signup($dto);

        return $this->successResponse(
            message: 'User created successfully.',
            data: $response->toArray(),
            status: 201,
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $dto = LoginRequestDto::fromArray($request->validated());

        $response = $this->authService->login($dto);

        return $this->successResponse(
            message: 'Login successful.',
            data: $response->toArray(),
            status: 200,
        );
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var PersonalAccessToken $token */
        $token = $request->user()->currentAccessToken();

        $this->authService->logout($token);

        return $this->successResponse(
            message: 'Logged out successfully.',
            status: 200,
        );
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $dto = ForgotPasswordRequestDto::fromArray($request->validated());

        $this->authService->forgotPassword($dto);

        // Always the same response, whether or not the account exists.
        return $this->successResponse(
            message: 'If an account with that email exists, a password reset link has been sent.',
            status: 200,
        );
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $dto = ResetPasswordRequestDto::fromArray($request->validated());

        $this->authService->resetPassword($dto);

        return $this->successResponse(
            message: 'Your password has been reset successfully.',
            status: 200,
        );
    }

    public function verifyEmail(Request $request, string $id, string $hash): JsonResponse
    {
        $this->authService->verifyEmail($id, $hash);

        return $this->successResponse(
            message: 'Email verified successfully.',
            status: 200,
        );
    }

    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $this->authService->resendVerificationEmail($request->user());

        return $this->successResponse(
            message: 'Verification email sent.',
            status: 200,
        );
    }
}
