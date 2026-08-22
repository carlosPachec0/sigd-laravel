<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Application\Services\AcademyService;
use App\Application\Services\AuthService;
use App\Application\Services\PaymentService;
use App\Application\Services\ProfileService;
use App\Application\Services\StudentService;
use App\Domain\Contracts\Repositories\AcademyRepositoryInterface;
use App\Domain\Contracts\Repositories\PaymentRepositoryInterface;
use App\Domain\Contracts\Repositories\StudentRepositoryInterface;
use App\Domain\Contracts\Repositories\UserRepositoryInterface;
use App\Infrastructure\Repositories\AcademyRepository;
use App\Infrastructure\Repositories\PaymentRepository;
use App\Infrastructure\Repositories\StudentRepository;
use App\Infrastructure\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class,
        );

        $this->app->bind(
            AcademyRepositoryInterface::class,
            AcademyRepository::class,
        );

        $this->app->bind(
            StudentRepositoryInterface::class,
            StudentRepository::class,
        );

        $this->app->bind(
            PaymentRepositoryInterface::class,
            PaymentRepository::class,
        );

        $this->app->bind(
            AuthService::class,
            function ($app) {
                return new AuthService(
                    $app->make(UserRepositoryInterface::class),
                );
            }
        );

        $this->app->bind(
            ProfileService::class,
            function ($app) {
                return new ProfileService(
                    $app->make(UserRepositoryInterface::class),
                );
            }
        );

        $this->app->bind(
            AcademyService::class,
            function ($app) {
                return new AcademyService(
                    $app->make(AcademyRepositoryInterface::class),
                );
            }
        );

        $this->app->bind(
            StudentService::class,
            function ($app) {
                return new StudentService(
                    $app->make(AcademyRepositoryInterface::class),
                    $app->make(StudentRepositoryInterface::class),
                );
            }
        );

        $this->app->bind(
            PaymentService::class,
            function ($app) {
                return new PaymentService(
                    $app->make(AcademyRepositoryInterface::class),
                    $app->make(StudentRepositoryInterface::class),
                    $app->make(PaymentRepositoryInterface::class),
                );
            }
        );
    }
}
