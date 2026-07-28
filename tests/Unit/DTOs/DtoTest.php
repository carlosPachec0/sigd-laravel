<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs;

use App\Application\DTOs\LoginRequestDto;
use App\Application\DTOs\LoginResponseDto;
use App\Application\DTOs\SignupRequestDto;
use App\Application\DTOs\SignupResponseDto;
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
        );

        $array = $dto->toArray();

        $this->assertSame([
            'id' => '1',
            'email' => 'test@example.com',
            'name' => 'John Doe',
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
        );

        $array = $dto->toArray();

        $this->assertSame([
            'id' => '1',
            'email' => 'test@example.com',
            'name' => 'John Doe',
        ], $array);
    }
}
