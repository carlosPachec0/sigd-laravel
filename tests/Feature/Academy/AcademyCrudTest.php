<?php

declare(strict_types=1);

namespace Tests\Feature\Academy;

use App\Domain\Entities\Academy;
use App\Domain\Entities\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AcademyCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    private function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    private function academyPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Judo Club',
            'discipline' => 'Judo',
            'registration_fee' => '50.00',
            'monthly_fee' => '25.00',
            'class_fee' => '10.00',
        ], $overrides);
    }

    private function createAcademy(User $owner, array $attributes = []): Academy
    {
        return Academy::create(array_merge([
            'user_id' => $owner->id,
            'name' => 'Karate Dojo',
            'discipline' => 'Karate',
            'registration_fee' => '60.00',
            'monthly_fee' => '30.00',
            'class_fee' => '15.00',
        ], $attributes));
    }

    #[Test]
    public function it_lists_only_the_authenticated_users_academies(): void
    {
        $this->createAcademy($this->user, ['name' => 'Mine']);
        $other = User::factory()->create();
        $this->createAcademy($other, ['name' => 'Not Mine']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/academies');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [['id', 'user_id', 'name', 'discipline', 'registration_fee', 'monthly_fee', 'class_fee', 'created_at', 'updated_at']],
                'status',
                'errors',
            ])
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [['name' => 'Mine']]]);
    }

    #[Test]
    public function it_creates_an_academy_for_the_authenticated_user(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/academies', $this->academyPayload());

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'user_id' => (string) $this->user->id,
                    'name' => 'Judo Club',
                    'discipline' => 'Judo',
                    'registration_fee' => '50.00',
                ],
            ]);

        $this->assertDatabaseHas('academies', [
            'user_id' => $this->user->id,
            'name' => 'Judo Club',
        ]);
    }

    #[Test]
    public function it_shows_an_owned_academy(): void
    {
        $academy = $this->createAcademy($this->user);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/academies/{$academy->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => ['id' => (string) $academy->id, 'name' => 'Karate Dojo'],
            ]);
    }

    #[Test]
    public function it_returns_404_for_an_academy_owned_by_another_user(): void
    {
        $other = User::factory()->create();
        $academy = $this->createAcademy($other);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/academies/{$academy->id}");

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Academy not found.',
                'status' => 404,
            ]);
    }

    #[Test]
    public function it_returns_404_for_a_non_existent_academy(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/academies/999999');

        $response->assertStatus(404);
    }

    #[Test]
    public function it_updates_an_owned_academy(): void
    {
        $academy = $this->createAcademy($this->user);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/academies/{$academy->id}", [
                'name' => 'Renamed Dojo',
                'discipline' => 'Judo',
                'registration_fee' => '70.00',
                'monthly_fee' => '35.00',
                'class_fee' => '20.00',
            ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['name' => 'Renamed Dojo']]);

        $this->assertDatabaseHas('academies', ['id' => $academy->id, 'name' => 'Renamed Dojo']);
    }

    #[Test]
    public function it_returns_404_when_updating_an_academy_owned_by_another_user(): void
    {
        $other = User::factory()->create();
        $academy = $this->createAcademy($other);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/academies/{$academy->id}", $this->academyPayload());

        $response->assertStatus(404);
    }

    #[Test]
    public function it_rejects_invalid_payload_on_store(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/academies', [
                'name' => '',
                'discipline' => '',
                'registration_fee' => '-5',
                'monthly_fee' => 'abc',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Validation failed.',
                'status' => 422,
            ]);
    }

    #[Test]
    public function it_deletes_an_owned_academy_using_soft_delete(): void
    {
        $academy = $this->createAcademy($this->user);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/academies/{$academy->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('academies', ['id' => $academy->id]);
    }

    #[Test]
    public function it_returns_404_when_deleting_an_academy_owned_by_another_user(): void
    {
        $other = User::factory()->create();
        $academy = $this->createAcademy($other);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/academies/{$academy->id}");

        $response->assertStatus(404);
    }

    #[Test]
    public function it_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/api/v1/academies')->assertStatus(401);
        $this->postJson('/api/v1/academies', $this->academyPayload())->assertStatus(401);
        $this->getJson('/api/v1/academies/1')->assertStatus(401);
        $this->putJson('/api/v1/academies/1', $this->academyPayload())->assertStatus(401);
        $this->deleteJson('/api/v1/academies/1')->assertStatus(401);
    }
}
