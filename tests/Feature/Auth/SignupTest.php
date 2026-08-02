<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Entities\User;
use App\Infrastructure\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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
            'name' => 'John Doe',
        ];
    }

    #[Test]
    public function it_registers_a_new_user_successfully(): void
    {
        $response = $this->postJson('/api/v1/auth/signup', $this->validPayload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'email', 'name', 'token', 'email_verified_at'],
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
            'name' => 'John Doe',
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
    public function it_returns_a_bearer_token_on_signup(): void
    {
        $response = $this->postJson('/api/v1/auth/signup', $this->validPayload);

        $response->assertStatus(201);

        $user = User::where('email', 'newuser@example.com')->first();

        $this->assertNotNull($user);
        $this->assertIsString($response->json('data.token'));
        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    #[Test]
    public function it_sends_email_verification_notification_on_signup(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/signup', $this->validPayload);

        $response->assertStatus(201);

        $user = User::where('email', 'newuser@example.com')->first();

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    #[Test]
    public function it_hashes_the_password(): void
    {
        $this->postJson('/api/v1/auth/signup', $this->validPayload);

        $user = User::where('email', 'newuser@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNotSame('StrongPass1!', $user->password);
        $this->assertTrue(Hash::check('StrongPass1!', $user->password));
    }
}
