<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\Repositories\AcademyRepositoryInterface;
use App\Domain\Entities\Academy;
use Illuminate\Support\Collection;

final class AcademyRepository implements AcademyRepositoryInterface
{
    public function findByIdForUser(string $id, string $userId): ?Academy
    {
        return Academy::where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function getForUserId(string $userId): Collection
    {
        return Academy::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(array $data): Academy
    {
        return Academy::create($data);
    }

    public function update(Academy $academy, array $data): Academy
    {
        $academy->forceFill($data);
        $academy->save();

        return $academy->fresh();
    }

    public function delete(Academy $academy): void
    {
        $academy->delete();
    }
}
