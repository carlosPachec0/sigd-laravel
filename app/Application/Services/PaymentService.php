<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\DTOs\PaymentRequestDto;
use App\Application\DTOs\PaymentResponseDto;
use App\Domain\Contracts\Repositories\AcademyRepositoryInterface;
use App\Domain\Contracts\Repositories\PaymentRepositoryInterface;
use App\Domain\Contracts\Repositories\StudentRepositoryInterface;
use App\Domain\Entities\Academy;
use App\Domain\Entities\Payment;
use App\Domain\Entities\Student;
use App\Domain\Entities\User;
use App\Domain\Exceptions\AcademyNotFoundException;
use App\Domain\Exceptions\PaymentNotFoundException;
use App\Domain\Exceptions\StudentNotFoundException;

final class PaymentService
{
    public function __construct(
        private readonly AcademyRepositoryInterface $academyRepository,
        private readonly StudentRepositoryInterface $studentRepository,
        private readonly PaymentRepositoryInterface $paymentRepository,
    ) {}

    /**
     * @return array<PaymentResponseDto>
     */
    public function index(User $user, string $academyId, string $studentId): array
    {
        $this->findOwnedStudent($user, $academyId, $studentId);

        return $this->paymentRepository
            ->getForStudentId($studentId)
            ->map(fn (Payment $payment) => PaymentResponseDto::fromPayment($payment))
            ->all();
    }

    public function show(User $user, string $academyId, string $studentId, string $paymentId): PaymentResponseDto
    {
        $payment = $this->findOwnedPayment($user, $academyId, $studentId, $paymentId);

        return PaymentResponseDto::fromPayment($payment);
    }

    public function store(User $user, string $academyId, string $studentId, PaymentRequestDto $dto): PaymentResponseDto
    {
        $student = $this->findOwnedStudent($user, $academyId, $studentId);

        $payment = $this->paymentRepository->create([
            'student_id' => $student->id,
            'subject' => $dto->subject,
            'amount' => $dto->amount,
        ]);

        return PaymentResponseDto::fromPayment($payment);
    }

    public function update(User $user, string $academyId, string $studentId, string $paymentId, PaymentRequestDto $dto): PaymentResponseDto
    {
        $payment = $this->findOwnedPayment($user, $academyId, $studentId, $paymentId);

        $updated = $this->paymentRepository->update($payment, [
            'subject' => $dto->subject ?? $payment->subject,
            'amount' => $dto->amount ?? $payment->amount,
        ]);

        return PaymentResponseDto::fromPayment($updated);
    }

    public function destroy(User $user, string $academyId, string $studentId, string $paymentId): void
    {
        $payment = $this->findOwnedPayment($user, $academyId, $studentId, $paymentId);

        $this->paymentRepository->delete($payment);
    }

    private function findOwnedAcademy(User $user, string $academyId): Academy
    {
        $academy = $this->academyRepository->findByIdForUser($academyId, (string) $user->id);

        if ($academy === null) {
            throw new AcademyNotFoundException;
        }

        return $academy;
    }

    private function findOwnedStudent(User $user, string $academyId, string $studentId): Student
    {
        $this->findOwnedAcademy($user, $academyId);

        $student = $this->studentRepository->findByIdForAcademy($studentId, $academyId);

        if ($student === null) {
            throw new StudentNotFoundException;
        }

        return $student;
    }

    private function findOwnedPayment(User $user, string $academyId, string $studentId, string $paymentId): Payment
    {
        $this->findOwnedStudent($user, $academyId, $studentId);

        $payment = $this->paymentRepository->findByIdForStudent($paymentId, $studentId);

        if ($payment === null) {
            throw new PaymentNotFoundException;
        }

        return $payment;
    }
}
