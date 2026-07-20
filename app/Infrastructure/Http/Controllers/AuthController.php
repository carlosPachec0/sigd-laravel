<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\DTOs\LoginRequestDto;
use App\Application\DTOs\SignupRequestDto;
use App\Application\Services\AuthService;
use App\Infrastructure\Http\Requests\LoginRequest;
use App\Infrastructure\Http\Requests\SignupRequest;
use App\Infrastructure\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

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
}
