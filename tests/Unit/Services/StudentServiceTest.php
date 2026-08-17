<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Application\DTOs\StudentRequestDto;
use App\Application\Services\StudentService;
use App\Domain\Contracts\Repositories\AcademyRepositoryInterface;
use App\Domain\Contracts\Repositories\StudentRepositoryInterface;
use App\Domain\Entities\Academy;
use App\Domain\Entities\Student;
use App\Domain\Entities\User;
use App\Domain\Exceptions\AcademyNotFoundException;
use App\Domain\Exceptions\StudentNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StudentServiceTest extends TestCase
{
    use RefreshDatabase;

    private AcademyRepositoryInterface|MockInterface $academyRepository;

    private StudentRepositoryInterface|MockInterface $studentRepository;

    private StudentService $studentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academyRepository = Mockery::mock(AcademyRepositoryInterface::class);
        $this->studentRepository = Mockery::mock(StudentRepositoryInterface::class);
        $this->studentService = new StudentService($this->academyRepository, $this->studentRepository);
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

    #[Test]
    public function it_lists_only_students_of_the_owned_academy(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $student = $this->makeStudent($academy);

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->with((string) $academy->id, (string) $user->id)
            ->andReturn($academy);

        $this->studentRepository
            ->shouldReceive('getForAcademyId')
            ->once()
            ->with((string) $academy->id)
            ->andReturn(new Collection([$student]));

        $result = $this->studentService->index($user, (string) $academy->id);

        $this->assertCount(1, $result);
        $this->assertSame('Jane Doe', $result[0]->name);
    }

    #[Test]
    public function it_throws_when_listing_students_of_an_unowned_academy(): void
    {
        $user = $this->makeUser();

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->andReturnNull();

        $this->expectException(AcademyNotFoundException::class);

        $this->studentService->index($user, 'missing-academy');
    }

    #[Test]
    public function it_creates_a_student_for_the_owned_academy(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $dto = new StudentRequestDto(
            name: 'John Doe',
            gender: 'Male',
            birthDate: '2010-05-10',
            height: '1.65',
            weight: '55.5',
        );

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->with((string) $academy->id, (string) $user->id)
            ->andReturn($academy);

        $this->studentRepository
            ->shouldReceive('create')
            ->once()
            ->with([
                'academy_id' => $academy->id,
                'name' => 'John Doe',
                'gender' => 'Male',
                'birth_date' => '2010-05-10',
                'height' => '1.65',
                'weight' => '55.5',
            ])
            ->andReturn($this->makeStudent($academy, ['name' => 'John Doe', 'gender' => 'Male']));

        $result = $this->studentService->store($user, (string) $academy->id, $dto);

        $this->assertSame('John Doe', $result->name);
        $this->assertSame((string) $academy->id, $result->academyId);
    }

    #[Test]
    public function it_throws_when_creating_a_student_for_an_unowned_academy(): void
    {
        $user = $this->makeUser();
        $dto = new StudentRequestDto(name: 'X', gender: 'Male', birthDate: '2010-01-01');

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->andReturnNull();

        $this->expectException(AcademyNotFoundException::class);

        $this->studentService->store($user, 'missing-academy', $dto);
    }

    #[Test]
    public function it_shows_an_owned_student(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $student = $this->makeStudent($academy);

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

        $result = $this->studentService->show($user, (string) $academy->id, (string) $student->id);

        $this->assertSame((string) $student->id, $result->id);
    }

    #[Test]
    public function it_throws_when_showing_a_student_the_user_does_not_own(): void
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

        $this->studentService->show($user, (string) $academy->id, 'does-not-exist');
    }

    #[Test]
    public function it_updates_an_owned_student(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $student = $this->makeStudent($academy);
        $dto = new StudentRequestDto(
            name: 'Renamed',
            gender: 'Female',
            birthDate: '2012-08-20',
            height: '1.60',
            weight: '50.0',
        );

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

        $updated = $student->replicate();
        $updated->name = 'Renamed';
        $updated->exists = true;
        $updated->id = $student->id;

        $this->studentRepository
            ->shouldReceive('update')
            ->once()
            ->with($student, Mockery::on(fn (array $data) => $data['name'] === 'Renamed'))
            ->andReturn($updated);

        $result = $this->studentService->update($user, (string) $academy->id, (string) $student->id, $dto);

        $this->assertSame('Renamed', $result->name);
    }

    #[Test]
    public function it_throws_when_updating_a_student_of_a_different_academy(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $dto = new StudentRequestDto(name: 'X', gender: 'Male', birthDate: '2010-01-01');

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->andReturn($academy);

        $this->studentRepository
            ->shouldReceive('findByIdForAcademy')
            ->once()
            ->andReturnNull();

        $this->expectException(StudentNotFoundException::class);

        $this->studentService->update($user, (string) $academy->id, 'missing', $dto);
    }

    #[Test]
    public function it_deletes_an_owned_student(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $student = $this->makeStudent($academy);

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

        $this->studentRepository
            ->shouldReceive('delete')
            ->once()
            ->with($student);

        $this->studentService->destroy($user, (string) $academy->id, (string) $student->id);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_throws_when_deleting_a_student_the_user_does_not_own(): void
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

        $this->studentService->destroy($user, (string) $academy->id, 'missing');
    }
}
