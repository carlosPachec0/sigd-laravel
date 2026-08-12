<?php

declare(strict_types=1);

namespace Tests\Feature\Student;

use App\Domain\Entities\Academy;
use App\Domain\Entities\Student;
use App\Domain\Entities\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StudentCrudTest extends TestCase
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

    private function studentPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'John Doe',
            'gender' => 'Male',
            'birth_date' => '2010-05-10',
            'height' => '1.65',
            'weight' => '55.5',
        ], $overrides);
    }

    private function createStudent(Academy $academy, array $attributes = []): Student
    {
        return Student::create(array_merge([
            'academy_id' => $academy->id,
            'name' => 'Jane Doe',
            'gender' => 'Female',
            'birth_date' => '2012-08-20',
            'height' => '1.55',
            'weight' => '48.0',
        ], $attributes));
    }

    #[Test]
    public function it_lists_only_the_students_of_the_owned_academy(): void
    {
        $academy = $this->createAcademy($this->user);
        $otherAcademy = $this->createAcademy($this->user, ['name' => 'Other Dojo']);
        $this->createStudent($academy, ['name' => 'Mine']);
        $this->createStudent($otherAcademy, ['name' => 'Not In This Academy']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/academies/{$academy->id}/students");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [['id', 'academy_id', 'name', 'gender', 'birth_date', 'height', 'weight', 'created_at', 'updated_at']],
                'status',
                'errors',
            ])
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [['name' => 'Mine']]]);
    }

    #[Test]
    public function it_creates_a_student_in_the_owned_academy(): void
    {
        $academy = $this->createAcademy($this->user);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/academies/{$academy->id}/students", $this->studentPayload());

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'academy_id' => (string) $academy->id,
                    'name' => 'John Doe',
                    'gender' => 'Male',
                    'birth_date' => '2010-05-10',
                    'height' => '1.65',
                    'weight' => '55.50',
                ],
            ]);

        $this->assertDatabaseHas('students', [
            'academy_id' => $academy->id,
            'name' => 'John Doe',
        ]);
    }

    #[Test]
    public function it_shows_a_student_of_the_owned_academy(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/academies/{$academy->id}/students/{$student->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => ['id' => (string) $student->id, 'name' => 'Jane Doe'],
            ]);
    }

    #[Test]
    public function it_returns_404_when_the_academy_belongs_to_another_user(): void
    {
        $other = User::factory()->create();
        $academy = $this->createAcademy($other);

        $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/academies/{$academy->id}/students")
            ->assertStatus(404)
            ->assertJson(['message' => 'Academy not found.', 'status' => 404]);

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/academies/{$academy->id}/students", $this->studentPayload())
            ->assertStatus(404);
    }

    #[Test]
    public function it_returns_404_for_a_student_that_belongs_to_a_different_academy(): void
    {
        $academy = $this->createAcademy($this->user);
        $otherAcademy = $this->createAcademy($this->user);
        $student = $this->createStudent($otherAcademy);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/academies/{$academy->id}/students/{$student->id}");

        $response->assertStatus(404)
            ->assertJson(['message' => 'Student not found.', 'status' => 404]);
    }

    #[Test]
    public function it_returns_404_for_a_non_existent_student(): void
    {
        $academy = $this->createAcademy($this->user);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/academies/{$academy->id}/students/999999");

        $response->assertStatus(404);
    }

    #[Test]
    public function it_updates_a_student_of_the_owned_academy(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/academies/{$academy->id}/students/{$student->id}", [
                'name' => 'Renamed Student',
            ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['name' => 'Renamed Student']]);

        $this->assertDatabaseHas('students', ['id' => $student->id, 'name' => 'Renamed Student']);
    }

    #[Test]
    public function it_returns_404_when_updating_a_student_of_a_different_academy(): void
    {
        $academy = $this->createAcademy($this->user);
        $otherAcademy = $this->createAcademy($this->user);
        $student = $this->createStudent($otherAcademy);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/academies/{$academy->id}/students/{$student->id}", $this->studentPayload());

        $response->assertStatus(404);
    }

    #[Test]
    public function it_rejects_invalid_payload_on_store(): void
    {
        $academy = $this->createAcademy($this->user);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/academies/{$academy->id}/students", [
                'name' => '',
                'gender' => 'Unknown',
                'birth_date' => 'not-a-date',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Validation failed.',
                'status' => 422,
            ]);
    }

    #[Test]
    public function it_deletes_a_student_of_the_owned_academy_using_soft_delete(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/academies/{$academy->id}/students/{$student->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('students', ['id' => $student->id]);
    }

    #[Test]
    public function it_returns_404_when_deleting_a_student_of_a_different_academy(): void
    {
        $academy = $this->createAcademy($this->user);
        $otherAcademy = $this->createAcademy($this->user);
        $student = $this->createStudent($otherAcademy);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/academies/{$academy->id}/students/{$student->id}");

        $response->assertStatus(404);
    }

    #[Test]
    public function it_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/api/v1/academies/1/students')->assertStatus(401);
        $this->postJson('/api/v1/academies/1/students', $this->studentPayload())->assertStatus(401);
        $this->getJson('/api/v1/academies/1/students/1')->assertStatus(401);
        $this->putJson('/api/v1/academies/1/students/1', $this->studentPayload())->assertStatus(401);
        $this->deleteJson('/api/v1/academies/1/students/1')->assertStatus(401);
    }
}
