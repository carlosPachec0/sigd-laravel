<?php

declare(strict_types=1);

namespace App\Domain\Contracts\Repositories;

use App\Domain\Entities\Payment;
use Illuminate\Support\Collection;

interface PaymentRepositoryInterface
{
    public function findByIdForStudent(string $id, string $studentId): ?Payment;

    public function getForStudentId(string $studentId): Collection;

    public function create(array $data): Payment;

    public function update(Payment $payment, array $data): Payment;

    public function delete(Payment $payment): void;
}
