<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Domain\Entities\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_the_authenticated_users_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'email', 'name', 'email_verified_at'],
                'status',
                'errors',
            ])
            ->assertJson([
                'data' => [
                    'email' => 'jane@example.com',
                    'name' => 'Jane Doe',
                ],
            ]);
    }

    #[Test]
    public function it_rejects_unauthenticated_requests(): void
    {
        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(401);
    }
}
