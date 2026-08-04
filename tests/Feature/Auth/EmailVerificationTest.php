<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Entities\User;
use App\Infrastructure\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => null]);
    }

    private function signedVerificationUrl(?\DateTimeInterface $expiration = null): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            $expiration ?? now()->addMinutes(60),
            [
                'id' => $this->user->getKey(),
                'hash' => sha1($this->user->getEmailForVerification()),
            ],
        );
    }

    #[Test]
    public function it_verifies_the_email_with_a_valid_signed_link(): void
    {
        $response = $this->getJson($this->signedVerificationUrl());

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Email verified successfully.',
                'status' => 200,
            ]);

        $this->assertNotNull($this->user->fresh()->email_verified_at);
    }

    #[Test]
    public function it_rejects_an_expired_signed_link(): void
    {
        $url = $this->signedVerificationUrl(now()->addMinutes(60));

        $this->travel(61)->minutes();

        $response = $this->getJson($url);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'This verification link is invalid or has expired.',
                'status' => 403,
            ]);

        $this->assertNull($this->user->fresh()->email_verified_at);
    }

    #[Test]
    public function it_rejects_a_tampered_hash(): void
    {
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $this->user->getKey(), 'hash' => sha1('someone-else@example.com')],
        );

        $response = $this->getJson($url);

        $response->assertStatus(403);
        $this->assertNull($this->user->fresh()->email_verified_at);
    }

    #[Test]
    public function it_is_idempotent_when_already_verified(): void
    {
        $this->user->markEmailAsVerified();

        $response = $this->getJson($this->signedVerificationUrl());

        $response->assertStatus(200);
    }

    #[Test]
    public function it_resends_the_verification_email_when_unverified(): void
    {
        Notification::fake();

        $token = $this->user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/email/verification-notification');

        $response->assertStatus(200);

        Notification::assertSentTo($this->user, VerifyEmailNotification::class);
    }

    #[Test]
    public function it_does_not_resend_when_already_verified(): void
    {
        Notification::fake();

        $this->user->markEmailAsVerified();
        $token = $this->user->createToken('api-token')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/email/verification-notification')
            ->assertStatus(200);

        Notification::assertNotSentTo($this->user, VerifyEmailNotification::class);
    }

    #[Test]
    public function it_rejects_resend_without_authentication(): void
    {
        $response = $this->postJson('/api/v1/auth/email/verification-notification');

        $response->assertStatus(401);
    }
}
