<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Domain\Entities\User;
use App\Infrastructure\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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
            'id' => (string) Str::uuid(),
            'email' => 'newuser@example.com',
            'password' => 'hashed_password',
            'name' => 'Jane Doe',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('newuser@example.com', $user->email);
        $this->assertSame('Jane Doe', $user->name);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'Jane Doe',
        ]);
    }
}
