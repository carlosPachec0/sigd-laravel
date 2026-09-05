<?php

declare(strict_types=1);

namespace Tests\Feature\Assistance;

use App\Domain\Entities\Academy;
use App\Domain\Entities\Assistance;
use App\Domain\Entities\Student;
use App\Domain\Entities\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssistanceCrudTest extends TestCase
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

    private function assistancePayload(array $overrides = []): array
    {
        return array_merge([
            'date' => '2026-08-20',
        ], $overrides);
    }

    private function createAssistance(Student $student, array $attributes = []): Assistance
    {
        return Assistance::create(array_merge([
            'student_id' => $student->id,
            'date' => '2026-08-20',
        ], $attributes));
    }

    #[Test]
    public function it_lists_only_the_assistance_records_of_the_owned_student(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);
        $otherStudent = $this->createStudent($academy, ['name' => 'Other Student']);
        $this->createAssistance($student, ['date' => '2026-08-20']);
        $this->createAssistance($otherStudent, ['date' => '2026-08-21']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/academies/{$academy->id}/students/{$student->id}/assistance");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [['id', 'student_id', 'date', 'created_at', 'updated_at']],
                'status',
                'errors',
            ])
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [['date' => '2026-08-20']]]);
    }

    #[Test]
    public function it_creates_an_assistance_record_for_the_owned_student(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/academies/{$academy->id}/students/{$student->id}/assistance", $this->assistancePayload());

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'student_id' => (string) $student->id,
                    'date' => '2026-08-20',
                ],
            ]);

        $this->assertDatabaseHas('assistance', [
            'student_id' => $student->id,
        ]);
    }

    #[Test]
    public function it_shows_an_assistance_record_of_the_owned_student(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);
        $assistance = $this->createAssistance($student);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/academies/{$academy->id}/students/{$student->id}/assistance/{$assistance->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => ['id' => (string) $assistance->id, 'date' => '2026-08-20'],
            ]);
    }

    #[Test]
    public function it_returns_404_when_the_academy_belongs_to_another_user(): void
    {
        $other = User::factory()->create();
        $academy = $this->createAcademy($other);
        $student = $this->createStudent($academy);

        $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/academies/{$academy->id}/students/{$student->id}/assistance")
            ->assertStatus(404)
            ->assertJson(['message' => 'Academy not found.', 'status' => 404]);

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/academies/{$academy->id}/students/{$student->id}/assistance", $this->assistancePayload())
            ->assertStatus(404);
    }

    #[Test]
    public function it_returns_404_for_an_assistance_record_that_belongs_to_a_different_student(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);
        $otherStudent = $this->createStudent($academy, ['name' => 'Other Student']);
        $assistance = $this->createAssistance($otherStudent);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/academies/{$academy->id}/students/{$student->id}/assistance/{$assistance->id}");

        $response->assertStatus(404)
            ->assertJson(['message' => 'Assistance record not found.', 'status' => 404]);
    }

    #[Test]
    public function it_returns_404_for_a_non_existent_assistance_record(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/academies/{$academy->id}/students/{$student->id}/assistance/does-not-exist");

        $response->assertStatus(404);
    }

    #[Test]
    public function it_updates_an_assistance_record_of_the_owned_student(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);
        $assistance = $this->createAssistance($student);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/academies/{$academy->id}/students/{$student->id}/assistance/{$assistance->id}", [
                'date' => '2026-08-21',
            ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['date' => '2026-08-21']]);

        $this->assertDatabaseHas('assistance', ['id' => $assistance->id]);
        $this->assertSame('2026-08-21', $assistance->fresh()->date->toDateString());
    }

    #[Test]
    public function it_returns_404_when_updating_an_assistance_record_of_a_different_student(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);
        $otherStudent = $this->createStudent($academy, ['name' => 'Other Student']);
        $assistance = $this->createAssistance($otherStudent);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/academies/{$academy->id}/students/{$student->id}/assistance/{$assistance->id}", $this->assistancePayload());

        $response->assertStatus(404);
    }

    #[Test]
    public function it_rejects_invalid_payload_on_store(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/academies/{$academy->id}/students/{$student->id}/assistance", [
                'date' => 'not-a-date',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Validation failed.',
                'status' => 422,
            ]);
    }

    #[Test]
    public function it_rejects_a_duplicate_assistance_record_for_the_same_student_and_date(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);
        $this->createAssistance($student, ['date' => '2026-08-20']);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/academies/{$academy->id}/students/{$student->id}/assistance", $this->assistancePayload(['date' => '2026-08-20']));

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'An assistance record already exists for this student on this date.',
                'status' => 409,
            ]);
    }

    #[Test]
    public function it_rejects_updating_an_assistance_record_to_a_date_already_used_by_the_same_student(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);
        $this->createAssistance($student, ['date' => '2026-08-20']);
        $assistance = $this->createAssistance($student, ['date' => '2026-08-21']);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/academies/{$academy->id}/students/{$student->id}/assistance/{$assistance->id}", [
                'date' => '2026-08-20',
            ]);

        $response->assertStatus(409);
    }

    #[Test]
    public function it_deletes_an_assistance_record_of_the_owned_student(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);
        $assistance = $this->createAssistance($student);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/academies/{$academy->id}/students/{$student->id}/assistance/{$assistance->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('assistance', ['id' => $assistance->id]);
    }

    #[Test]
    public function it_returns_404_when_deleting_an_assistance_record_of_a_different_student(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);
        $otherStudent = $this->createStudent($academy, ['name' => 'Other Student']);
        $assistance = $this->createAssistance($otherStudent);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/academies/{$academy->id}/students/{$student->id}/assistance/{$assistance->id}");

        $response->assertStatus(404);
    }

    #[Test]
    public function it_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/api/v1/academies/1/students/1/assistance')->assertStatus(401);
        $this->postJson('/api/v1/academies/1/students/1/assistance', $this->assistancePayload())->assertStatus(401);
        $this->getJson('/api/v1/academies/1/students/1/assistance/1')->assertStatus(401);
        $this->putJson('/api/v1/academies/1/students/1/assistance/1', $this->assistancePayload())->assertStatus(401);
        $this->deleteJson('/api/v1/academies/1/students/1/assistance/1')->assertStatus(401);
    }
}
