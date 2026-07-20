<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Constants\UserRoles;
use App\Domain\Entities\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SignupTest extends TestCase
{
    use RefreshDatabase;

    private array $validPayload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validPayload = [
            'email' => 'newuser@example.com',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role' => UserRoles::STANDARD,
        ];
    }

    #[Test]
    public function it_registers_a_new_user_successfully(): void
    {
        $response = $this->postJson('/api/v1/auth/signup', $this->validPayload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'email', 'first_name', 'last_name', 'role'],
                'status',
                'errors',
            ])
            ->assertJson([
                'message' => 'User created successfully.',
                'status' => 201,
                'errors' => [],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role' => UserRoles::STANDARD,
        ]);
    }

    #[Test]
    public function it_rejects_duplicate_email(): void
    {
        $this->postJson('/api/v1/auth/signup', $this->validPayload);

        $response = $this->postJson('/api/v1/auth/signup', $this->validPayload);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'A user with email \'newuser@example.com\' already exists.',
                'data' => null,
                'status' => 409,
            ]);
    }

    #[Test]
    public function it_rejects_invalid_email_format(): void
    {
        $payload = array_merge($this->validPayload, ['email' => 'not-an-email']);

        $response = $this->postJson('/api/v1/auth/signup', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Validation failed.',
                'data' => null,
                'status' => 422,
            ])
            ->assertJsonStructure(['errors']);
    }

    #[Test]
    public function it_rejects_weak_password(): void
    {
        $payload = array_merge($this->validPayload, [
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response = $this->postJson('/api/v1/auth/signup', $payload);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_rejects_missing_password_confirmation(): void
    {
        $payload = $this->validPayload;
        unset($payload['password_confirmation']);

        $response = $this->postJson('/api/v1/auth/signup', $payload);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_rejects_missing_required_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/signup', []);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Validation failed.',
                'status' => 422,
            ]);
    }

    #[Test]
    public function it_rejects_invalid_role(): void
    {
        $payload = array_merge($this->validPayload, ['role' => 'InvalidRole']);

        $response = $this->postJson('/api/v1/auth/signup', $payload);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_authenticates_user_on_signup(): void
    {
        $response = $this->postJson('/api/v1/auth/signup', $this->validPayload);

        $response->assertStatus(201);

        $user = User::where('email', 'newuser@example.com')->first();

        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user, 'web');
    }

    #[Test]
    public function it_hashes_the_password(): void
    {
        $this->postJson('/api/v1/auth/signup', $this->validPayload);

        $user = User::where('email', 'newuser@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNotSame('StrongPass1!', $user->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('StrongPass1!', $user->password));
    }
}
