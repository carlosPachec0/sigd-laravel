<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\DTOs\StudentRequestDto;
use App\Application\DTOs\StudentResponseDto;
use App\Application\Services\StudentService;
use App\Infrastructure\Http\Requests\StoreStudentRequest;
use App\Infrastructure\Http\Requests\UpdateStudentRequest;
use App\Infrastructure\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StudentController
{
    use ApiResponse;

    public function __construct(
        private readonly StudentService $studentService,
    ) {}

    public function index(Request $request, string $academyId): JsonResponse
    {
        $students = array_map(
            fn (StudentResponseDto $dto) => $dto->toArray(),
            $this->studentService->index($request->user(), $academyId),
        );

        return $this->successResponse(
            message: 'Students retrieved successfully.',
            data: $students,
            status: 200,
        );
    }

    public function store(StoreStudentRequest $request, string $academyId): JsonResponse
    {
        $dto = StudentRequestDto::fromArray($request->validated());

        $response = $this->studentService->store($request->user(), $academyId, $dto);

        return $this->successResponse(
            message: 'Student created successfully.',
            data: $response->toArray(),
            status: 201,
        );
    }

    public function show(Request $request, string $academyId, string $studentId): JsonResponse
    {
        $response = $this->studentService->show($request->user(), $academyId, $studentId);

        return $this->successResponse(
            message: 'Student retrieved successfully.',
            data: $response->toArray(),
            status: 200,
        );
    }

    public function update(UpdateStudentRequest $request, string $academyId, string $studentId): JsonResponse
    {
        $dto = StudentRequestDto::fromArray($request->validated());

        $response = $this->studentService->update($request->user(), $academyId, $studentId, $dto);

        return $this->successResponse(
            message: 'Student updated successfully.',
            data: $response->toArray(),
            status: 200,
        );
    }

    public function destroy(Request $request, string $academyId, string $studentId): JsonResponse
    {
        $this->studentService->destroy($request->user(), $academyId, $studentId);

        return $this->successResponse(
            message: 'Student deleted successfully.',
            status: 204,
        );
    }
}
