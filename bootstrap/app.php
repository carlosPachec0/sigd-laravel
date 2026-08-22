<?php

use App\Domain\Exceptions\AcademyNotFoundException;
use App\Domain\Exceptions\InvalidCredentialsException;
use App\Domain\Exceptions\InvalidCurrentPasswordException;
use App\Domain\Exceptions\InvalidPasswordResetTokenException;
use App\Domain\Exceptions\InvalidVerificationLinkException;
use App\Domain\Exceptions\PaymentNotFoundException;
use App\Domain\Exceptions\StudentNotFoundException;
use App\Domain\Exceptions\UserAlreadyExistsException;
use App\Infrastructure\Http\Middleware\ForceJsonResponse;
use App\Infrastructure\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
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

        $middleware->api(append: [
            SecurityHeaders::class,
        ]);
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

        $exceptions->renderable(function (AcademyNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
                'status' => 404,
                'errors' => [$e->getMessage()],
            ], 404);
        });

        $exceptions->renderable(function (StudentNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
                'status' => 404,
                'errors' => [$e->getMessage()],
            ], 404);
        });

        $exceptions->renderable(function (PaymentNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
                'status' => 404,
                'errors' => [$e->getMessage()],
            ], 404);
        });

        $exceptions->renderable(function (InvalidCredentialsException $e) {
            return response()->json([
                'message' => 'Authentication failed.',
                'data' => null,
                'status' => 401,
                'errors' => [$e->getMessage()],
            ], 401);
        });

        $exceptions->renderable(function (InvalidCurrentPasswordException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
                'status' => 422,
                'errors' => [$e->getMessage()],
            ], 422);
        });

        $exceptions->renderable(function (InvalidVerificationLinkException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
                'status' => 403,
                'errors' => [$e->getMessage()],
            ], 403);
        });

        $exceptions->renderable(function (InvalidPasswordResetTokenException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'data' => null,
                'status' => 422,
                'errors' => [$e->getMessage()],
            ], 422);
        });

        $exceptions->renderable(function (AuthenticationException $e) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'data' => null,
                'status' => 401,
                'errors' => ['Unauthenticated.'],
            ], 401);
        });

        $exceptions->renderable(function (InvalidSignatureException $e) {
            return response()->json([
                'message' => 'This verification link is invalid or has expired.',
                'data' => null,
                'status' => 403,
                'errors' => ['This verification link is invalid or has expired.'],
            ], 403);
        });

        $exceptions->renderable(function (HttpResponseException $e) {
            return $e->getResponse();
        });

        $exceptions->renderable(function (Throwable $e) {
            return response()->json([
                'message' => 'Internal server error.',
                'data' => null,
                'status' => 500,
                'errors' => ['An unexpected error occurred.'],
            ], 500);
        });
    })->create();
