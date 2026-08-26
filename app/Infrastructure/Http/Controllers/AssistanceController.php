<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\DTOs\AssistanceRequestDto;
use App\Application\DTOs\AssistanceResponseDto;
use App\Application\Services\AssistanceService;
use App\Infrastructure\Http\Requests\StoreAssistanceRequest;
use App\Infrastructure\Http\Requests\UpdateAssistanceRequest;
use App\Infrastructure\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AssistanceController
{
    use ApiResponse;

    public function __construct(
        private readonly AssistanceService $assistanceService,
    ) {}

    public function index(Request $request, string $academyId, string $studentId): JsonResponse
    {
        $assistance = array_map(
            fn (AssistanceResponseDto $dto) => $dto->toArray(),
            $this->assistanceService->index($request->user(), $academyId, $studentId),
        );

        return $this->successResponse(
            message: 'Assistance records retrieved successfully.',
            data: $assistance,
            status: 200,
        );
    }

    public function store(StoreAssistanceRequest $request, string $academyId, string $studentId): JsonResponse
    {
        $dto = AssistanceRequestDto::fromArray($request->validated());

        $response = $this->assistanceService->store($request->user(), $academyId, $studentId, $dto);

        return $this->successResponse(
            message: 'Assistance record created successfully.',
            data: $response->toArray(),
            status: 201,
        );
    }

    public function show(Request $request, string $academyId, string $studentId, string $assistanceId): JsonResponse
    {
        $response = $this->assistanceService->show($request->user(), $academyId, $studentId, $assistanceId);

        return $this->successResponse(
            message: 'Assistance record retrieved successfully.',
            data: $response->toArray(),
            status: 200,
        );
    }

    public function update(UpdateAssistanceRequest $request, string $academyId, string $studentId, string $assistanceId): JsonResponse
    {
        $dto = AssistanceRequestDto::fromArray($request->validated());

        $response = $this->assistanceService->update($request->user(), $academyId, $studentId, $assistanceId, $dto);

        return $this->successResponse(
            message: 'Assistance record updated successfully.',
            data: $response->toArray(),
            status: 200,
        );
    }

    public function destroy(Request $request, string $academyId, string $studentId, string $assistanceId): JsonResponse
    {
        $this->assistanceService->destroy($request->user(), $academyId, $studentId, $assistanceId);

        return $this->successResponse(
            message: 'Assistance record deleted successfully.',
            status: 204,
        );
    }
}
