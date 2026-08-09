<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Application\DTOs\AcademyRequestDto;
use App\Application\Services\AcademyService;
use App\Domain\Contracts\Repositories\AcademyRepositoryInterface;
use App\Domain\Entities\Academy;
use App\Domain\Entities\User;
use App\Domain\Exceptions\AcademyNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AcademyServiceTest extends TestCase
{
    use RefreshDatabase;

    private AcademyRepositoryInterface|MockInterface $academyRepository;

    private AcademyService $academyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academyRepository = Mockery::mock(AcademyRepositoryInterface::class);
        $this->academyService = new AcademyService($this->academyRepository);
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

    #[Test]
    public function it_lists_only_academies_owned_by_the_user(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);

        $this->academyRepository
            ->shouldReceive('getForUserId')
            ->once()
            ->with((string) $user->id)
            ->andReturn(new Collection([$academy]));

        $result = $this->academyService->index($user);

        $this->assertCount(1, $result);
        $this->assertSame('Judo Club', $result[0]->name);
    }

    #[Test]
    public function it_creates_an_academy_for_the_authenticated_user(): void
    {
        $user = $this->makeUser();
        $dto = new AcademyRequestDto(
            name: 'Karate Dojo',
            discipline: 'Karate',
            registrationFee: '60.00',
            monthlyFee: '30.00',
            classFee: '15.00',
        );

        $this->academyRepository
            ->shouldReceive('create')
            ->once()
            ->with([
                'user_id' => (string) $user->id,
                'name' => 'Karate Dojo',
                'discipline' => 'Karate',
                'registration_fee' => '60.00',
                'monthly_fee' => '30.00',
                'class_fee' => '15.00',
            ])
            ->andReturn($this->makeAcademy($user, ['name' => 'Karate Dojo', 'discipline' => 'Karate']));

        $result = $this->academyService->store($user, $dto);

        $this->assertSame('Karate Dojo', $result->name);
        $this->assertSame((string) $user->id, $result->userId);
    }

    #[Test]
    public function it_shows_an_owned_academy(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->with((string) $academy->id, (string) $user->id)
            ->andReturn($academy);

        $result = $this->academyService->show($user, (string) $academy->id);

        $this->assertSame((string) $academy->id, $result->id);
    }

    #[Test]
    public function it_throws_when_showing_an_academy_the_user_does_not_own(): void
    {
        $user = $this->makeUser();

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->andReturnNull();

        $this->expectException(AcademyNotFoundException::class);

        $this->academyService->show($user, 'does-not-exist');
    }

    #[Test]
    public function it_updates_an_owned_academy(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);
        $dto = new AcademyRequestDto(
            name: 'Renamed',
            discipline: 'Judo',
            registrationFee: '50.00',
            monthlyFee: '25.00',
            classFee: '10.00',
        );

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->with((string) $academy->id, (string) $user->id)
            ->andReturn($academy);

        $updated = $academy->replicate();
        $updated->name = 'Renamed';
        $updated->exists = true;
        $updated->id = $academy->id;

        $this->academyRepository
            ->shouldReceive('update')
            ->once()
            ->with($academy, Mockery::on(fn (array $data) => $data['name'] === 'Renamed'))
            ->andReturn($updated);

        $result = $this->academyService->update($user, (string) $academy->id, $dto);

        $this->assertSame('Renamed', $result->name);
    }

    #[Test]
    public function it_throws_when_updating_an_academy_the_user_does_not_own(): void
    {
        $user = $this->makeUser();
        $dto = new AcademyRequestDto(name: 'X', discipline: 'Y', registrationFee: '1', monthlyFee: '1', classFee: '1');

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->andReturnNull();

        $this->expectException(AcademyNotFoundException::class);

        $this->academyService->update($user, 'missing', $dto);
    }

    #[Test]
    public function it_deletes_an_owned_academy(): void
    {
        $user = $this->makeUser();
        $academy = $this->makeAcademy($user);

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->with((string) $academy->id, (string) $user->id)
            ->andReturn($academy);

        $this->academyRepository
            ->shouldReceive('delete')
            ->once()
            ->with($academy);

        $this->academyService->destroy($user, (string) $academy->id);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_throws_when_deleting_an_academy_the_user_does_not_own(): void
    {
        $user = $this->makeUser();

        $this->academyRepository
            ->shouldReceive('findByIdForUser')
            ->once()
            ->andReturnNull();

        $this->expectException(AcademyNotFoundException::class);

        $this->academyService->destroy($user, 'missing');
    }
}
