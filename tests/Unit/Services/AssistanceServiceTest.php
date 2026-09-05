<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Application\DTOs\AssistanceRequestDto;
use App\Application\Services\AssistanceService;
use App\Domain\Contracts\Repositories\AcademyRepositoryInterface;
use App\Domain\Contracts\Repositories\AssistanceRepositoryInterface;
use App\Domain\Contracts\Repositories\StudentRepositoryInterface;
use App\Domain\Entities\Academy;
use App\Domain\Entities\Assistance;
use App\Domain\Entities\Student;
use App\Domain\Entities\User;
use App\Domain\Exceptions\AcademyNotFoundException;
use App\Domain\Exceptions\AssistanceAlreadyExistsException;
use App\Domain\Exceptions\AssistanceNotFoundException;
use App\Domain\Exceptions\StudentNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssistanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private AcademyRepositoryInterface|MockInterface $academyRepository;

    private StudentRepositoryInterface|MockInterface $studentRepository;

    private AssistanceRepositoryInterface|MockInterface $assistanceRepository;

    private AssistanceService $assistanceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academyRepository = Mockery::mock(AcademyRepositoryInterface::class);
        $this->studentRepository = Mockery::mock(StudentRepositoryInterface::class);
        $this->assistanceRepository = Mockery::mock(AssistanceRepositoryInterface::class);
        $this->assistanceService = new AssistanceService(
            $this->academyRepository,
            $this->studentRepository,
            $this->assistanceRepository,
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

    private function makeAssistance(Student $student, array $attributes = []): Assistance
    {
        return Assistance::create(array_merge([
            'student_id' => $student->id,
            'date' => '2026-08-20',
        ], $attributes));
    }

    #[Test]
    public function it_lists_only_assistance_records_of_the_owned_student(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $student = $this->makeStudent($academy);
        $assistance = $this->makeAssistance($student);

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

        $this->assistanceRepository
            ->shouldReceive('getForStudentId')
            ->once()
            ->with((string) $student->id)
            ->andReturn(new Collection([$assistance]));

        $result = $this->assistanceService->index($user, (string) $academy->id, (string) $student->id);

        $this->assertCount(1, $result);
        $this->assertSame('2026-08-20', $result[0]->date);
    }

    #[Test]
    public function it_throws_when_listing_assistance_records_of_an_unowned_academy(): void
    {
        $user = $this->makeUser();

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->andReturnNull();

        $this->expectException(AcademyNotFoundException::class);

        $this->assistanceService->index($user, 'missing-academy', 'missing-student');
    }

    #[Test]
    public function it_throws_when_listing_assistance_records_of_a_student_not_in_the_academy(): void
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

        $this->assistanceService->index($user, (string) $academy->id, 'missing-student');
    }

    #[Test]
    public function it_creates_an_assistance_record_for_the_owned_student(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $student = $this->makeStudent($academy);
        $dto = new AssistanceRequestDto(date: '2026-08-20');

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

        $this->assistanceRepository
            ->shouldReceive('findByStudentAndDate')
            ->once()
            ->with((string) $student->id, '2026-08-20', null)
            ->andReturnNull();

        $this->assistanceRepository
            ->shouldReceive('create')
            ->once()
            ->with([
                'student_id' => $student->id,
                'date' => '2026-08-20',
            ])
            ->andReturn($this->makeAssistance($student, ['date' => '2026-08-20']));

        $result = $this->assistanceService->store($user, (string) $academy->id, (string) $student->id, $dto);

        $this->assertSame('2026-08-20', $result->date);
        $this->assertSame((string) $student->id, $result->studentId);
    }

    #[Test]
    public function it_throws_when_creating_an_assistance_record_for_an_unowned_student(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $dto = new AssistanceRequestDto(date: '2026-08-20');

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->andReturn($academy);

        $this->studentRepository
            ->shouldReceive('findByIdForAcademy')
            ->once()
            ->andReturnNull();

        $this->expectException(StudentNotFoundException::class);

        $this->assistanceService->store($user, (string) $academy->id, 'missing-student', $dto);
    }

    #[Test]
    public function it_throws_when_creating_a_duplicate_assistance_record(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $student = $this->makeStudent($academy);
        $existing = $this->makeAssistance($student);
        $dto = new AssistanceRequestDto(date: '2026-08-20');

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->andReturn($academy);

        $this->studentRepository
            ->shouldReceive('findByIdForAcademy')
            ->once()
            ->andReturn($student);

        $this->assistanceRepository
            ->shouldReceive('findByStudentAndDate')
            ->once()
            ->with((string) $student->id, '2026-08-20', null)
            ->andReturn($existing);

        $this->expectException(AssistanceAlreadyExistsException::class);

        $this->assistanceService->store($user, (string) $academy->id, (string) $student->id, $dto);
    }

    #[Test]
    public function it_shows_an_owned_assistance_record(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $student = $this->makeStudent($academy);
        $assistance = $this->makeAssistance($student);

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

        $this->assistanceRepository
            ->shouldReceive('findByIdForStudent')
            ->once()
            ->with((string) $assistance->id, (string) $student->id)
            ->andReturn($assistance);

        $result = $this->assistanceService->show($user, (string) $academy->id, (string) $student->id, (string) $assistance->id);

        $this->assertSame((string) $assistance->id, $result->id);
    }

    #[Test]
    public function it_throws_when_showing_an_assistance_record_the_user_does_not_own(): void
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

        $this->assistanceRepository
            ->shouldReceive('findByIdForStudent')
            ->once()
            ->andReturnNull();

        $this->expectException(AssistanceNotFoundException::class);

        $this->assistanceService->show($user, (string) $academy->id, (string) $student->id, 'does-not-exist');
    }

    #[Test]
    public function it_updates_an_owned_assistance_record(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $student = $this->makeStudent($academy);
        $assistance = $this->makeAssistance($student, ['date' => '2026-08-20']);
        $dto = new AssistanceRequestDto(date: '2026-08-21');

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

        $this->assistanceRepository
            ->shouldReceive('findByIdForStudent')
            ->once()
            ->with((string) $assistance->id, (string) $student->id)
            ->andReturn($assistance);

        $this->assistanceRepository
            ->shouldReceive('findByStudentAndDate')
            ->once()
            ->with((string) $student->id, '2026-08-21', (string) $assistance->id)
            ->andReturnNull();

        $updated = $assistance->replicate();
        $updated->date = '2026-08-21';
        $updated->exists = true;
        $updated->id = $assistance->id;

        $this->assistanceRepository
            ->shouldReceive('update')
            ->once()
            ->with($assistance, Mockery::on(fn (array $data) => $data['date'] === '2026-08-21'))
            ->andReturn($updated);

        $result = $this->assistanceService->update($user, (string) $academy->id, (string) $student->id, (string) $assistance->id, $dto);

        $this->assertSame('2026-08-21', $result->date);
    }

    #[Test]
    public function it_throws_when_updating_an_assistance_record_to_a_date_already_used(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $student = $this->makeStudent($academy);
        $assistance = $this->makeAssistance($student, ['date' => '2026-08-20']);
        $conflicting = $this->makeAssistance($student, ['date' => '2026-08-21']);
        $dto = new AssistanceRequestDto(date: '2026-08-21');

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->andReturn($academy);

        $this->studentRepository
            ->shouldReceive('findByIdForAcademy')
            ->once()
            ->andReturn($student);

        $this->assistanceRepository
            ->shouldReceive('findByIdForStudent')
            ->once()
            ->andReturn($assistance);

        $this->assistanceRepository
            ->shouldReceive('findByStudentAndDate')
            ->once()
            ->with((string) $student->id, '2026-08-21', (string) $assistance->id)
            ->andReturn($conflicting);

        $this->expectException(AssistanceAlreadyExistsException::class);

        $this->assistanceService->update($user, (string) $academy->id, (string) $student->id, (string) $assistance->id, $dto);
    }

    #[Test]
    public function it_throws_when_updating_an_assistance_record_of_a_different_student(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $student = $this->makeStudent($academy);
        $dto = new AssistanceRequestDto(date: '2026-08-21');

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->andReturn($academy);

        $this->studentRepository
            ->shouldReceive('findByIdForAcademy')
            ->once()
            ->andReturn($student);

        $this->assistanceRepository
            ->shouldReceive('findByIdForStudent')
            ->once()
            ->andReturnNull();

        $this->expectException(AssistanceNotFoundException::class);

        $this->assistanceService->update($user, (string) $academy->id, (string) $student->id, 'missing', $dto);
    }

    #[Test]
    public function it_deletes_an_owned_assistance_record(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $student = $this->makeStudent($academy);
        $assistance = $this->makeAssistance($student);

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

        $this->assistanceRepository
            ->shouldReceive('findByIdForStudent')
            ->once()
            ->with((string) $assistance->id, (string) $student->id)
            ->andReturn($assistance);

        $this->assistanceRepository
            ->shouldReceive('delete')
            ->once()
            ->with($assistance);

        $this->assistanceService->destroy($user, (string) $academy->id, (string) $student->id, (string) $assistance->id);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_throws_when_deleting_an_assistance_record_the_user_does_not_own(): void
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

        $this->assistanceRepository
            ->shouldReceive('findByIdForStudent')
            ->once()
            ->andReturnNull();

        $this->expectException(AssistanceNotFoundException::class);

        $this->assistanceService->destroy($user, (string) $academy->id, (string) $student->id, 'missing');
    }
}
