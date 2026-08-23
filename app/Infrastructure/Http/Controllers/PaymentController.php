<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\DTOs\PaymentRequestDto;
use App\Application\DTOs\PaymentResponseDto;
use App\Application\Services\PaymentService;
use App\Infrastructure\Http\Requests\StorePaymentRequest;
use App\Infrastructure\Http\Requests\UpdatePaymentRequest;
use App\Infrastructure\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PaymentController
{
    use ApiResponse;

    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function index(Request $request, string $academyId, string $studentId): JsonResponse
    {
        $payments = array_map(
            fn (PaymentResponseDto $dto) => $dto->toArray(),
            $this->paymentService->index($request->user(), $academyId, $studentId),
        );

        return $this->successResponse(
            message: 'Payments retrieved successfully.',
            data: $payments,
            status: 200,
        );
    }

    public function store(StorePaymentRequest $request, string $academyId, string $studentId): JsonResponse
    {
        $dto = PaymentRequestDto::fromArray($request->validated());

        $response = $this->paymentService->store($request->user(), $academyId, $studentId, $dto);

        return $this->successResponse(
            message: 'Payment created successfully.',
            data: $response->toArray(),
            status: 201,
        );
    }

    public function show(Request $request, string $academyId, string $studentId, string $paymentId): JsonResponse
    {
        $response = $this->paymentService->show($request->user(), $academyId, $studentId, $paymentId);

        return $this->successResponse(
            message: 'Payment retrieved successfully.',
            data: $response->toArray(),
            status: 200,
        );
    }

    public function update(UpdatePaymentRequest $request, string $academyId, string $studentId, string $paymentId): JsonResponse
    {
        $dto = PaymentRequestDto::fromArray($request->validated());

        $response = $this->paymentService->update($request->user(), $academyId, $studentId, $paymentId, $dto);

        return $this->successResponse(
            message: 'Payment updated successfully.',
            data: $response->toArray(),
            status: 200,
        );
    }

    public function destroy(Request $request, string $academyId, string $studentId, string $paymentId): JsonResponse
    {
        $this->paymentService->destroy($request->user(), $academyId, $studentId, $paymentId);

        return $this->successResponse(
            message: 'Payment deleted successfully.',
            status: 204,
        );
    }
}
