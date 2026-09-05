<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\Repositories\AssistanceRepositoryInterface;
use App\Domain\Entities\Assistance;
use Illuminate\Support\Collection;

final class AssistanceRepository implements AssistanceRepositoryInterface
{
    public function findByIdForStudent(string $id, string $studentId): ?Assistance
    {
        return Assistance::where('id', $id)
            ->where('student_id', $studentId)
            ->first();
    }

    public function getForStudentId(string $studentId): Collection
    {
        return Assistance::where('student_id', $studentId)
            ->orderByDesc('date')
            ->get();
    }

    public function findByStudentAndDate(string $studentId, string $date, ?string $excludeId = null): ?Assistance
    {
        return Assistance::where('student_id', $studentId)
            ->whereDate('date', $date)
            ->when($excludeId !== null, fn ($query) => $query->where('id', '!=', $excludeId))
            ->first();
    }

    public function create(array $data): Assistance
    {
        return Assistance::create($data);
    }

    public function update(Assistance $assistance, array $data): Assistance
    {
        $assistance->forceFill($data);
        $assistance->save();

        return $assistance->fresh();
    }

    public function delete(Assistance $assistance): void
    {
        $assistance->delete();
    }
}
