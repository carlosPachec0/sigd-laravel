<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Entities\User;
use App\Infrastructure\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_a_generic_response_for_an_existing_email(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'existing@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'If an account with that email exists, a password reset link has been sent.',
                'status' => 200,
            ]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    #[Test]
    public function it_returns_the_identical_response_for_a_nonexistent_email(): void
    {
        $existingEmailResponse = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'idontexist@example.com',
        ]);

        $existingEmailResponse->assertStatus(200)
            ->assertJson([
                'message' => 'If an account with that email exists, a password reset link has been sent.',
                'status' => 200,
            ]);
    }

    #[Test]
    public function it_rejects_invalid_email_format(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_rejects_missing_email(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', []);

        $response->assertStatus(422);
    }
}
