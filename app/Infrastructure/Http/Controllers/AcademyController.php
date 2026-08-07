<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\DTOs\AcademyRequestDto;
use App\Application\DTOs\AcademyResponseDto;
use App\Application\Services\AcademyService;
use App\Infrastructure\Http\Requests\StoreAcademyRequest;
use App\Infrastructure\Http\Requests\UpdateAcademyRequest;
use App\Infrastructure\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AcademyController
{
    use ApiResponse;

    public function __construct(
        private readonly AcademyService $academyService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $academies = array_map(
            fn (AcademyResponseDto $dto) => $dto->toArray(),
            $this->academyService->index($request->user()),
        );

        return $this->successResponse(
            message: 'Academies retrieved successfully.',
            data: $academies,
            status: 200,
        );
    }

    public function store(StoreAcademyRequest $request): JsonResponse
    {
        $dto = AcademyRequestDto::fromArray($request->validated());

        $response = $this->academyService->store($request->user(), $dto);

        return $this->successResponse(
            message: 'Academy created successfully.',
            data: $response->toArray(),
            status: 201,
        );
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $response = $this->academyService->show($request->user(), $id);

        return $this->successResponse(
            message: 'Academy retrieved successfully.',
            data: $response->toArray(),
            status: 200,
        );
    }

    public function update(UpdateAcademyRequest $request, string $id): JsonResponse
    {
        $dto = AcademyRequestDto::fromArray($request->validated());

        $response = $this->academyService->update($request->user(), $id, $dto);

        return $this->successResponse(
            message: 'Academy updated successfully.',
            data: $response->toArray(),
            status: 200,
        );
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->academyService->destroy($request->user(), $id);

        return $this->successResponse(
            message: 'Academy deleted successfully.',
            status: 204,
        );
    }
}
