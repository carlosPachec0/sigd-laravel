<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Entities\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'reset-me@example.com',
            'password' => Hash::make('OldPassword1!'),
        ]);
    }

    #[Test]
    public function it_resets_the_password_with_a_valid_token(): void
    {
        $token = Password::createToken($this->user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'reset-me@example.com',
            'token' => $token,
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Your password has been reset successfully.',
                'status' => 200,
            ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'reset-me@example.com',
            'password' => 'NewPassword1!',
        ])->assertStatus(200);
    }

    #[Test]
    public function it_revokes_existing_tokens_on_reset(): void
    {
        $accessToken = $this->user->createToken('api-token');

        $token = Password::createToken($this->user);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'reset-me@example.com',
            'token' => $token,
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])->assertStatus(200);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $accessToken->accessToken->id]);
    }

    #[Test]
    public function it_rejects_an_invalid_token(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'reset-me@example.com',
            'token' => 'invalid-token',
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'This password reset link is invalid or has expired.',
                'status' => 422,
            ]);
    }

    #[Test]
    public function it_rejects_a_weak_new_password(): void
    {
        $token = Password::createToken($this->user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'reset-me@example.com',
            'token' => $token,
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422);
    }
}
