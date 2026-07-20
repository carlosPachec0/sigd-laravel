<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Domain\Entities\User;
use App\Infrastructure\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new UserRepository();
    }

    #[Test]
    public function it_finds_a_user_by_email(): void
    {
        $user = User::factory()->create([
            'email' => 'findme@example.com',
        ]);

        $found = $this->repository->findByEmail('findme@example.com');

        $this->assertNotNull($found);
        $this->assertSame($user->id, $found->id);
        $this->assertSame('findme@example.com', $found->email);
    }

    #[Test]
    public function it_returns_null_when_user_not_found_by_email(): void
    {
        $found = $this->repository->findByEmail('nonexistent@example.com');

        $this->assertNull($found);
    }

    #[Test]
    public function it_creates_a_new_user(): void
    {
        $user = $this->repository->create([
            'email' => 'newuser@example.com',
            'password' => 'hashed_password',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'role' => 'Standard',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('newuser@example.com', $user->email);
        $this->assertSame('Jane', $user->first_name);
        $this->assertSame('Doe', $user->last_name);
        $this->assertSame('Standard', $user->role);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'role' => 'Standard',
        ]);
    }
}
