<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\Repositories\PaymentRepositoryInterface;
use App\Domain\Entities\Payment;
use Illuminate\Support\Collection;

final class PaymentRepository implements PaymentRepositoryInterface
{
    public function findByIdForStudent(string $id, string $studentId): ?Payment
    {
        return Payment::where('id', $id)
            ->where('student_id', $studentId)
            ->first();
    }

    public function getForStudentId(string $studentId): Collection
    {
        return Payment::where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    public function update(Payment $payment, array $data): Payment
    {
        $payment->forceFill($data);
        $payment->save();

        return $payment->fresh();
    }

    public function delete(Payment $payment): void
    {
        $payment->delete();
    }
}
