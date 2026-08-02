<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Domain\Entities\User;
use App\Infrastructure\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    #[Test]
    public function it_updates_name_and_email(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson('/api/v1/profile', [
                'name' => 'New Name',
                'email' => 'old@example.com',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'New Name',
                    'email' => 'old@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'New Name',
        ]);
    }

    #[Test]
    public function it_resets_verification_and_resends_email_when_email_changes(): void
    {
        Notification::fake();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson('/api/v1/profile', [
                'name' => 'Old Name',
                'email' => 'new@example.com',
            ]);

        $response->assertStatus(200);

        $fresh = $this->user->fresh();
        $this->assertSame('new@example.com', $fresh->email);
        $this->assertNull($fresh->email_verified_at);

        Notification::assertSentTo($fresh, VerifyEmailNotification::class);
    }

    #[Test]
    public function it_rejects_an_email_belonging_to_another_user(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson('/api/v1/profile', [
                'name' => 'Old Name',
                'email' => 'taken@example.com',
            ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_rejects_an_invalid_email_format(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson('/api/v1/profile', [
                'name' => 'Old Name',
                'email' => 'not-an-email',
            ]);

        $response->assertStatus(422);
    }
}
