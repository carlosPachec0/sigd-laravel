<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Application\Services\AuthService;
use App\Domain\Contracts\Repositories\UserRepositoryInterface;
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
            AuthService::class,
            function ($app) {
                return new AuthService(
                    $app->make(UserRepositoryInterface::class),
                );
            }
        );
    }
}
