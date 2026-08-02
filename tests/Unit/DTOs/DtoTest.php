<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs;

use App\Application\DTOs\ChangePasswordRequestDto;
use App\Application\DTOs\ForgotPasswordRequestDto;
use App\Application\DTOs\LoginRequestDto;
use App\Application\DTOs\LoginResponseDto;
use App\Application\DTOs\ProfileResponseDto;
use App\Application\DTOs\ResetPasswordRequestDto;
use App\Application\DTOs\SignupRequestDto;
use App\Application\DTOs\SignupResponseDto;
use App\Application\DTOs\UpdateProfileRequestDto;
use App\Domain\Entities\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DtoTest extends TestCase
{
    #[Test]
    public function signup_request_dto_creates_from_array(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'name' => 'John Doe',
        ];

        $dto = SignupRequestDto::fromArray($data);

        $this->assertSame('test@example.com', $dto->email);
        $this->assertSame('Password123!', $dto->password);
        $this->assertSame('John Doe', $dto->name);
    }

    #[Test]
    public function signup_request_dto_converts_to_array(): void
    {
        $dto = new SignupRequestDto(
            email: 'test@example.com',
            password: 'Password123!',
            name: 'John Doe',
        );

        $array = $dto->toArray();

        $this->assertSame([
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'name' => 'John Doe',
        ], $array);
    }

    #[Test]
    public function signup_response_dto_converts_to_array(): void
    {
        $dto = new SignupResponseDto(
            id: '1',
            email: 'test@example.com',
            name: 'John Doe',
            token: 'plain-text-token',
        );

        $array = $dto->toArray();

        $this->assertSame([
            'id' => '1',
            'email' => 'test@example.com',
            'name' => 'John Doe',
            'token' => 'plain-text-token',
            'email_verified_at' => null,
        ], $array);
    }

    #[Test]
    public function login_request_dto_creates_from_array(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ];

        $dto = LoginRequestDto::fromArray($data);

        $this->assertSame('test@example.com', $dto->email);
        $this->assertSame('Password123!', $dto->password);
    }

    #[Test]
    public function login_response_dto_converts_to_array(): void
    {
        $dto = new LoginResponseDto(
            id: '1',
            email: 'test@example.com',
            name: 'John Doe',
            token: 'plain-text-token',
            emailVerifiedAt: '2026-07-31T00:00:00.000000Z',
        );

        $array = $dto->toArray();

        $this->assertSame([
            'id' => '1',
            'email' => 'test@example.com',
            'name' => 'John Doe',
            'token' => 'plain-text-token',
            'email_verified_at' => '2026-07-31T00:00:00.000000Z',
        ], $array);
    }

    #[Test]
    public function forgot_password_request_dto_creates_from_array(): void
    {
        $dto = ForgotPasswordRequestDto::fromArray(['email' => 'test@example.com']);

        $this->assertSame('test@example.com', $dto->email);
    }

    #[Test]
    public function reset_password_request_dto_creates_from_array(): void
    {
        $dto = ResetPasswordRequestDto::fromArray([
            'email' => 'test@example.com',
            'token' => 'some-token',
            'password' => 'NewPassword1!',
        ]);

        $this->assertSame('test@example.com', $dto->email);
        $this->assertSame('some-token', $dto->token);
        $this->assertSame('NewPassword1!', $dto->password);
    }

    #[Test]
    public function change_password_request_dto_creates_from_array(): void
    {
        $dto = ChangePasswordRequestDto::fromArray([
            'current_password' => 'OldPassword1!',
            'new_password' => 'NewPassword1!',
        ]);

        $this->assertSame('OldPassword1!', $dto->currentPassword);
        $this->assertSame('NewPassword1!', $dto->newPassword);
    }

    #[Test]
    public function update_profile_request_dto_creates_from_array(): void
    {
        $dto = UpdateProfileRequestDto::fromArray([
            'name' => 'John Doe',
            'email' => 'test@example.com',
        ]);

        $this->assertSame('John Doe', $dto->name);
        $this->assertSame('test@example.com', $dto->email);
    }

    #[Test]
    public function profile_response_dto_builds_from_user_and_converts_to_array(): void
    {
        $user = new User([
            'name' => 'John Doe',
            'email' => 'test@example.com',
        ]);
        $user->id = '1';
        $user->email_verified_at = null;

        $dto = ProfileResponseDto::fromUser($user);

        $this->assertSame([
            'id' => '1',
            'email' => 'test@example.com',
            'name' => 'John Doe',
            'email_verified_at' => null,
        ], $dto->toArray());
    }
}
