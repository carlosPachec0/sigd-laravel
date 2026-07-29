<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Application\DTOs\LoginRequestDto;
use App\Application\DTOs\SignupRequestDto;
use App\Application\Services\AuthService;
use App\Domain\Contracts\Repositories\UserRepositoryInterface;
use App\Domain\Entities\User;
use App\Domain\Exceptions\InvalidCredentialsException;
use App\Domain\Exceptions\UserAlreadyExistsException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryInterface|MockInterface $userRepository;
    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->authService = new AuthService($this->userRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createMockUser(array $attributes = []): User|MockInterface
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = $attributes['id'] ?? '1';
        $user->email = $attributes['email'] ?? 'test@example.com';
        $user->name = $attributes['name'] ?? 'John Doe';
        $user->password = $attributes['password'] ?? Hash::make('password');

        return $user;
    }

    #[Test]
    public function it_creates_a_new_user_on_signup(): void
    {
        $dto = new SignupRequestDto(
            email: 'test@example.com',
            password: 'Password123!',
            name: 'John Doe',
        );

        $user = $this->createMockUser([
            'id' => '1',
            'email' => 'test@example.com',
            'name' => 'John Doe',
        ]);

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->with('test@example.com')
            ->once()
            ->andReturn(null);

        $this->userRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($user);

        Auth::shouldReceive('guard')
            ->with('web')
            ->once()
            ->andReturn($guard = Mockery::mock());
        $guard->shouldReceive('login')
            ->with($user)
            ->once();

        $result = $this->authService->signup($dto);

        $this->assertSame('1', $result->id);
        $this->assertSame('test@example.com', $result->email);
        $this->assertSame('John Doe', $result->name);
    }

    #[Test]
    public function it_throws_exception_when_user_already_exists_on_signup(): void
    {
        $dto = new SignupRequestDto(
            email: 'existing@example.com',
            password: 'Password123!',
            name: 'John Doe',
        );

        $existingUser = $this->createMockUser(['email' => 'existing@example.com']);

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->with('existing@example.com')
            ->once()
            ->andReturn($existingUser);

        $this->expectException(UserAlreadyExistsException::class);

        $this->authService->signup($dto);
    }

    #[Test]
    public function it_returns_user_data_on_successful_login(): void
    {
        $dto = new LoginRequestDto(
            email: 'test@example.com',
            password: 'Password123!',
        );

        $user = $this->createMockUser([
            'id' => '1',
            'email' => 'test@example.com',
            'name' => 'John Doe',
            'password' => Hash::make('Password123!'),
        ]);

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->with('test@example.com')
            ->once()
            ->andReturn($user);

        Auth::shouldReceive('guard')
            ->with('web')
            ->once()
            ->andReturn($guard = Mockery::mock());
        $guard->shouldReceive('login')
            ->with($user)
            ->once();

        $result = $this->authService->login($dto);

        $this->assertSame('1', $result->id);
        $this->assertSame('test@example.com', $result->email);
        $this->assertSame('John Doe', $result->name);
    }

    #[Test]
    public function it_throws_exception_for_unknown_email_on_login(): void
    {
        $dto = new LoginRequestDto(
            email: 'unknown@example.com',
            password: 'Password123!',
        );

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->with('unknown@example.com')
            ->once()
            ->andReturn(null);

        $this->expectException(InvalidCredentialsException::class);

        $this->authService->login($dto);
    }

    #[Test]
    public function it_throws_exception_for_wrong_password_on_login(): void
    {
        $dto = new LoginRequestDto(
            email: 'test@example.com',
            password: 'WrongPassword!',
        );

        $user = $this->createMockUser([
            'password' => Hash::make('CorrectPassword!'),
        ]);

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->with('test@example.com')
            ->once()
            ->andReturn($user);

        $this->expectException(InvalidCredentialsException::class);

        $this->authService->login($dto);
    }
}
