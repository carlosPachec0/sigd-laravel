<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\Repositories\UserRepositoryInterface;
use App\Domain\Entities\User;

final class UserRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findById(string $id): ?User
    {
        return User::find($id);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        // forceFill (not fill) deliberately — lets trusted infrastructure-layer
        // callers update guarded columns like email_verified_at, while HTTP
        // mass assignment via $fillable stays tight.
        $user->forceFill($data);
        $user->save();

        return $user->fresh();
    }
}
