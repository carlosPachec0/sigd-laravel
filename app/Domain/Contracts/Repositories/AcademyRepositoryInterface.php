<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

use App\Domain\Entities\Academy;
use Illuminate\Support\Collection;

interface AcademyRepositoryInterface
{
    public function findByIdForUser(string $id, string $userId): ?Academy;

    public function getForUserId(string $userId): Collection;

    public function create(array $data): Academy;

    public function update(Academy $academy, array $data): Academy;

    public function delete(Academy $academy): void;
}
