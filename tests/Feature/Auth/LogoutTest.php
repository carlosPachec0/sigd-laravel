<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Domain\Entities\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_logs_out_successfully(): void
    {
        $token = $this->user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully.',
                'status' => 200,
            ]);
    }

    #[Test]
    public function it_revokes_only_the_current_token(): void
    {
        $currentToken = $this->user->createToken('current-device');
        $otherToken = $this->user->createToken('other-device');

        $this->withHeader('Authorization', "Bearer {$currentToken->plainTextToken}")
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $currentToken->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->accessToken->id]);
    }

    #[Test]
    public function it_rejects_logout_without_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
                'status' => 401,
            ]);
    }
}
