<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\DTOs\ChangePasswordRequestDto;
use App\Application\DTOs\UpdateProfileRequestDto;
use App\Application\Services\ProfileService;
use App\Infrastructure\Http\Requests\ChangePasswordRequest;
use App\Infrastructure\Http\Requests\UpdateProfileRequest;
use App\Infrastructure\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProfileController
{
    use ApiResponse;

    public function __construct(
        private readonly ProfileService $profileService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $response = $this->profileService->getProfile($request->user());

        return $this->successResponse(
            message: 'Profile retrieved successfully.',
            data: $response->toArray(),
            status: 200,
        );
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $dto = UpdateProfileRequestDto::fromArray($request->validated());

        $response = $this->profileService->updateProfile($request->user(), $dto);

        return $this->successResponse(
            message: 'Profile updated successfully.',
            data: $response->toArray(),
            status: 200,
        );
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $dto = ChangePasswordRequestDto::fromArray($request->validated());

        $this->profileService->changePassword($request->user(), $dto);

        return $this->successResponse(
            message: 'Password changed successfully.',
            status: 200,
        );
    }
}
