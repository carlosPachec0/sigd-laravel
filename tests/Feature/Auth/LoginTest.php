<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Constants\UserRoles;
use App\Domain\Entities\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('Password123!'),
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'role' => UserRoles::STANDARD,
        ]);
    }

    #[Test]
    public function it_authenticates_a_user_successfully(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'email', 'first_name', 'last_name', 'role'],
                'status',
                'errors',
            ])
            ->assertJson([
                'message' => 'Login successful.',
                'status' => 200,
                'errors' => [],
            ]);
    }

    #[Test]
    public function it_rejects_invalid_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Authentication failed.',
                'data' => null,
                'status' => 401,
            ]);
    }

    #[Test]
    public function it_rejects_unknown_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Authentication failed.',
                'data' => null,
                'status' => 401,
            ]);
    }

    #[Test]
    public function it_rejects_missing_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Validation failed.',
                'status' => 422,
            ]);
    }

    #[Test]
    public function it_rejects_missing_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Validation failed.',
                'status' => 422,
            ]);
    }

    #[Test]
    public function it_rejects_empty_payload(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_authenticates_user_on_login(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200);

        $this->assertAuthenticatedAs($this->user, 'web');
    }

    #[Test]
    public function it_returns_correct_user_data_on_login(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'email' => 'user@example.com',
                    'first_name' => 'Jane',
                    'last_name' => 'Doe',
                    'role' => UserRoles::STANDARD,
                ],
            ]);
    }
}
