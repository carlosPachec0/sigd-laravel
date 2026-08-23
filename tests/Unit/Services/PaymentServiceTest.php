<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Application\DTOs\PaymentRequestDto;
use App\Application\Services\PaymentService;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private AcademyRepositoryInterface|MockInterface $academyRepository;

    private StudentRepositoryInterface|MockInterface $studentRepository;

    private PaymentRepositoryInterface|MockInterface $paymentRepository;

    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academyRepository = Mockery::mock(AcademyRepositoryInterface::class);
        $this->studentRepository = Mockery::mock(StudentRepositoryInterface::class);
        $this->paymentRepository = Mockery::mock(PaymentRepositoryInterface::class);
        $this->paymentService = new PaymentService(
            $this->academyRepository,
            $this->studentRepository,
            $this->paymentRepository,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeUser(): User
    {
        return User::factory()->create();
    }

    private function makeAcademy(User $user, array $attributes = []): Academy
    {
        return Academy::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Judo Club',
            'discipline' => 'Judo',
            'registration_fee' => '50.00',
            'monthly_fee' => '25.00',
            'class_fee' => '10.00',
        ], $attributes));
    }

    private function makeStudent(Academy $academy, array $attributes = []): Student
    {
        return Student::create(array_merge([
            'academy_id' => $academy->id,
            'name' => 'Jane Doe',
            'gender' => 'Female',
            'birth_date' => '2012-08-20',
            'height' => '1.55',
            'weight' => '48.0',
        ], $attributes));
    }

    private function makePayment(Student $student, array $attributes = []): Payment
    {
        return Payment::create(array_merge([
            'student_id' => $student->id,
            'subject' => 'Registration fee',
            'amount' => '60.00',
        ], $attributes));
    }

    #[Test]
    public function it_lists_only_payments_of_the_owned_student(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $student = $this->makeStudent($academy);
        $payment = $this->makePayment($student);

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->with((string) $academy->id, (string) $user->id)
            ->andReturn($academy);

        $this->studentRepository
            ->shouldReceive('findByIdForAcademy')
            ->once()
            ->with((string) $student->id, (string) $academy->id)
            ->andReturn($student);

        $this->paymentRepository
            ->shouldReceive('getForStudentId')
            ->once()
            ->with((string) $student->id)
            ->andReturn(new Collection([$payment]));

        $result = $this->paymentService->index($user, (string) $academy->id, (string) $student->id);

        $this->assertCount(1, $result);
        $this->assertSame('Registration fee', $result[0]->subject);
    }

    #[Test]
    public function it_throws_when_listing_payments_of_an_unowned_academy(): void
    {
        $user = $this->makeUser();

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->andReturnNull();

        $this->expectException(AcademyNotFoundException::class);

        $this->paymentService->index($user, 'missing-academy', 'missing-student');
    }

    #[Test]
    public function it_throws_when_listing_payments_of_a_student_not_in_the_academy(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->andReturn($academy);

        $this->studentRepository
            ->shouldReceive('findByIdForAcademy')
            ->once()
            ->andReturnNull();

        $this->expectException(StudentNotFoundException::class);

        $this->paymentService->index($user, (string) $academy->id, 'missing-student');
    }

    #[Test]
    public function it_creates_a_payment_for_the_owned_student(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $student = $this->makeStudent($academy);
        $dto = new PaymentRequestDto(subject: 'Monthly fee', amount: '30.00');

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->with((string) $academy->id, (string) $user->id)
            ->andReturn($academy);

        $this->studentRepository
            ->shouldReceive('findByIdForAcademy')
            ->once()
            ->with((string) $student->id, (string) $academy->id)
            ->andReturn($student);

        $this->paymentRepository
            ->shouldReceive('create')
            ->once()
            ->with([
                'student_id' => $student->id,
                'subject' => 'Monthly fee',
                'amount' => '30.00',
            ])
            ->andReturn($this->makePayment($student, ['subject' => 'Monthly fee', 'amount' => '30.00']));

        $result = $this->paymentService->store($user, (string) $academy->id, (string) $student->id, $dto);

        $this->assertSame('Monthly fee', $result->subject);
        $this->assertSame((string) $student->id, $result->studentId);
    }

    #[Test]
    public function it_throws_when_creating_a_payment_for_an_unowned_student(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $dto = new PaymentRequestDto(subject: 'Monthly fee', amount: '30.00');

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->andReturn($academy);

        $this->studentRepository
            ->shouldReceive('findByIdForAcademy')
            ->once()
            ->andReturnNull();

        $this->expectException(StudentNotFoundException::class);

        $this->paymentService->store($user, (string) $academy->id, 'missing-student', $dto);
    }

    #[Test]
    public function it_shows_an_owned_payment(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $student = $this->makeStudent($academy);
        $payment = $this->makePayment($student);

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->with((string) $academy->id, (string) $user->id)
            ->andReturn($academy);

        $this->studentRepository
            ->shouldReceive('findByIdForAcademy')
            ->once()
            ->with((string) $student->id, (string) $academy->id)
            ->andReturn($student);

        $this->paymentRepository
            ->shouldReceive('findByIdForStudent')
            ->once()
            ->with((string) $payment->id, (string) $student->id)
            ->andReturn($payment);

        $result = $this->paymentService->show($user, (string) $academy->id, (string) $student->id, (string) $payment->id);

        $this->assertSame((string) $payment->id, $result->id);
    }

    #[Test]
    public function it_throws_when_showing_a_payment_the_user_does_not_own(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $student = $this->makeStudent($academy);

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->andReturn($academy);

        $this->studentRepository
            ->shouldReceive('findByIdForAcademy')
            ->once()
            ->andReturn($student);

        $this->paymentRepository
            ->shouldReceive('findByIdForStudent')
            ->once()
            ->andReturnNull();

        $this->expectException(PaymentNotFoundException::class);

        $this->paymentService->show($user, (string) $academy->id, (string) $student->id, 'does-not-exist');
    }

    #[Test]
    public function it_updates_an_owned_payment(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $student = $this->makeStudent($academy);
        $payment = $this->makePayment($student);
        $dto = new PaymentRequestDto(subject: 'Renamed', amount: '45.00');

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->with((string) $academy->id, (string) $user->id)
            ->andReturn($academy);

        $this->studentRepository
            ->shouldReceive('findByIdForAcademy')
            ->once()
            ->with((string) $student->id, (string) $academy->id)
            ->andReturn($student);

        $this->paymentRepository
            ->shouldReceive('findByIdForStudent')
            ->once()
            ->with((string) $payment->id, (string) $student->id)
            ->andReturn($payment);

        $updated = $payment->replicate();
        $updated->subject = 'Renamed';
        $updated->exists = true;
        $updated->id = $payment->id;

        $this->paymentRepository
            ->shouldReceive('update')
            ->once()
            ->with($payment, Mockery::on(fn (array $data) => $data['subject'] === 'Renamed'))
            ->andReturn($updated);

        $result = $this->paymentService->update($user, (string) $academy->id, (string) $student->id, (string) $payment->id, $dto);

        $this->assertSame('Renamed', $result->subject);
    }

    #[Test]
    public function it_throws_when_updating_a_payment_of_a_different_student(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $student = $this->makeStudent($academy);
        $dto = new PaymentRequestDto(subject: 'X', amount: '10.00');

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->andReturn($academy);

        $this->studentRepository
            ->shouldReceive('findByIdForAcademy')
            ->once()
            ->andReturn($student);

        $this->paymentRepository
            ->shouldReceive('findByIdForStudent')
            ->once()
            ->andReturnNull();

        $this->expectException(PaymentNotFoundException::class);

        $this->paymentService->update($user, (string) $academy->id, (string) $student->id, 'missing', $dto);
    }

    #[Test]
    public function it_deletes_an_owned_payment(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $student = $this->makeStudent($academy);
        $payment = $this->makePayment($student);

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->with((string) $academy->id, (string) $user->id)
            ->andReturn($academy);

        $this->studentRepository
            ->shouldReceive('findByIdForAcademy')
            ->once()
            ->with((string) $student->id, (string) $academy->id)
            ->andReturn($student);

        $this->paymentRepository
            ->shouldReceive('findByIdForStudent')
            ->once()
            ->with((string) $payment->id, (string) $student->id)
            ->andReturn($payment);

        $this->paymentRepository
            ->shouldReceive('delete')
            ->once()
            ->with($payment);

        $this->paymentService->destroy($user, (string) $academy->id, (string) $student->id, (string) $payment->id);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_throws_when_deleting_a_payment_the_user_does_not_own(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $student = $this->makeStudent($academy);

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->andReturn($academy);

        $this->studentRepository
            ->shouldReceive('findByIdForAcademy')
            ->once()
            ->andReturn($student);

        $this->paymentRepository
            ->shouldReceive('findByIdForStudent')
            ->once()
            ->andReturnNull();

        $this->expectException(PaymentNotFoundException::class);

        $this->paymentService->destroy($user, (string) $academy->id, (string) $student->id, 'missing');
    }
}
