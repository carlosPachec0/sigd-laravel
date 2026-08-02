<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Application\DTOs\ChangePasswordRequestDto;
use App\Application\DTOs\UpdateProfileRequestDto;
use App\Application\Services\ProfileService;
use App\Domain\Contracts\Repositories\UserRepositoryInterface;
use App\Domain\Entities\User;
use App\Domain\Exceptions\InvalidCurrentPasswordException;
use App\Infrastructure\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryInterface|MockInterface $userRepository;

    private ProfileService $profileService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->profileService = new ProfileService($this->userRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_returns_the_users_profile(): void
    {
        $user = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);

        $result = $this->profileService->getProfile($user);

        $this->assertSame('John Doe', $result->name);
        $this->assertSame('john@example.com', $result->email);
    }

    #[Test]
    public function it_updates_the_profile_without_touching_verification_when_email_is_unchanged(): void
    {
        Notification::fake();

        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'same@example.com']);
        $dto = new UpdateProfileRequestDto(name: 'New Name', email: 'same@example.com');

        $this->userRepository
            ->shouldReceive('update')
            ->once()
            ->with($user, ['name' => 'New Name', 'email' => 'same@example.com'])
            ->andReturn($user->fill(['name' => 'New Name']));

        $result = $this->profileService->updateProfile($user, $dto);

        $this->assertSame('New Name', $result->name);
        Notification::assertNothingSent();
    }

    #[Test]
    public function it_resets_verification_and_notifies_when_email_changes(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'old@example.com', 'email_verified_at' => now()]);
        $dto = new UpdateProfileRequestDto(name: $user->name, email: 'new@example.com');

        $updatedUser = $user->replicate();
        $updatedUser->email = 'new@example.com';
        $updatedUser->email_verified_at = null;
        $updatedUser->exists = true;
        $updatedUser->id = $user->id;

        $this->userRepository
            ->shouldReceive('update')
            ->once()
            ->with($user, ['name' => $user->name, 'email' => 'new@example.com', 'email_verified_at' => null])
            ->andReturn($updatedUser);

        $result = $this->profileService->updateProfile($user, $dto);

        $this->assertSame('new@example.com', $result->email);
        $this->assertNull($result->emailVerifiedAt);
        Notification::assertSentTo($updatedUser, VerifyEmailNotification::class);
    }

    #[Test]
    public function it_changes_the_password_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('CurrentPassword1!')]);
        $dto = new ChangePasswordRequestDto(currentPassword: 'CurrentPassword1!', newPassword: 'NewPassword1!');

        $this->userRepository
            ->shouldReceive('update')
            ->once()
            ->with($user, ['password' => 'NewPassword1!']);

        $this->profileService->changePassword($user, $dto);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_throws_when_current_password_is_incorrect(): void
    {
        $user = User::factory()->create(['password' => Hash::make('CurrentPassword1!')]);
        $dto = new ChangePasswordRequestDto(currentPassword: 'WrongPassword!', newPassword: 'NewPassword1!');

        $this->expectException(InvalidCurrentPasswordException::class);

        $this->profileService->changePassword($user, $dto);
    }
}
