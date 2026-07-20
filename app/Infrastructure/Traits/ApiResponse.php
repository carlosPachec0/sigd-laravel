<?php

declare(strict_types=1);

namespace App\Infrastructure\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function successResponse(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
            'status' => $status,
            'errors' => [],
        ], $status);
    }

    protected function errorResponse(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => null,
            'status' => $status,
            'errors' => $errors,
        ], $status);
    }
}
