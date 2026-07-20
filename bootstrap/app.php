<?php

use App\Domain\Exceptions\InvalidCredentialsException;
use App\Domain\Exceptions\UserAlreadyExistsException;
use App\Infrastructure\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn () => true,
        );

        $exceptions->renderable(function (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'data' => null,
                'status' => 422,
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->renderable(function (UserAlreadyExistsException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
                'status' => 409,
                'errors' => [$e->getMessage()],
            ], 409);
        });

        $exceptions->renderable(function (InvalidCredentialsException $e) {
            return response()->json([
                'message' => 'Authentication failed.',
                'data' => null,
                'status' => 401,
                'errors' => [$e->getMessage()],
            ], 401);
        });

        $exceptions->renderable(function (HttpResponseException $e) {
            return $e->getResponse();
        });

        $exceptions->renderable(function (\Throwable $e) {
            return response()->json([
                'message' => 'Internal server error.',
                'data' => null,
                'status' => 500,
                'errors' => ['An unexpected error occurred.'],
            ], 500);
        });
    })->create();
