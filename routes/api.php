<?php

use App\Infrastructure\Http\Controllers\AcademyController;
use App\Infrastructure\Http\Controllers\AuthController;
use App\Infrastructure\Http\Controllers\PaymentController;
use App\Infrastructure\Http\Controllers\ProfileController;
use App\Infrastructure\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('signup', [AuthController::class, 'signup'])->middleware('throttle:signup');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:forgot-password');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:forgot-password');
        Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->middleware(['signed', 'throttle:verify-email'])
            ->name('verification.verify');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
                ->middleware('throttle:resend-verification');
        });
    });

    Route::middleware('auth:sanctum')->prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::put('password', [ProfileController::class, 'changePassword']);
    });

    Route::middleware('auth:sanctum')->prefix('academies')->group(function () {
        Route::get('/', [AcademyController::class, 'index']);
        Route::post('/', [AcademyController::class, 'store']);
        Route::get('{id}', [AcademyController::class, 'show']);
        Route::put('{id}', [AcademyController::class, 'update']);
        Route::delete('{id}', [AcademyController::class, 'destroy']);

        Route::prefix('{academyId}/students')->group(function () {
            Route::get('/', [StudentController::class, 'index']);
            Route::post('/', [StudentController::class, 'store']);
            Route::get('{studentId}', [StudentController::class, 'show']);
            Route::put('{studentId}', [StudentController::class, 'update']);
            Route::delete('{studentId}', [StudentController::class, 'destroy']);

            Route::prefix('{studentId}/payments')->group(function () {
                Route::get('/', [PaymentController::class, 'index']);
                Route::post('/', [PaymentController::class, 'store']);
                Route::get('{paymentId}', [PaymentController::class, 'show']);
                Route::put('{paymentId}', [PaymentController::class, 'update']);
                Route::delete('{paymentId}', [PaymentController::class, 'destroy']);
            });
        });
    });
});
