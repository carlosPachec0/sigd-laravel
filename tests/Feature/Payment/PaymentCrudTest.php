<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Domain\Entities\Academy;
use App\Domain\Entities\Payment;
use App\Domain\Entities\Student;
use App\Domain\Entities\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentCrudTest extends TestCase
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

    private function paymentPayload(array $overrides = []): array
    {
        return array_merge([
            'subject' => 'Monthly fee',
            'amount' => '30.00',
        ], $overrides);
    }

    private function createPayment(Student $student, array $attributes = []): Payment
    {
        return Payment::create(array_merge([
            'student_id' => $student->id,
            'subject' => 'Registration fee',
            'amount' => '60.00',
        ], $attributes));
    }

    #[Test]
    public function it_lists_only_the_payments_of_the_owned_student(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);
        $otherStudent = $this->createStudent($academy, ['name' => 'Other Student']);
        $this->createPayment($student, ['subject' => 'Mine']);
        $this->createPayment($otherStudent, ['subject' => 'Not Mine']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/academies/{$academy->id}/students/{$student->id}/payments");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [['id', 'student_id', 'subject', 'amount', 'created_at', 'updated_at']],
                'status',
                'errors',
            ])
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [['subject' => 'Mine']]]);
    }

    #[Test]
    public function it_creates_a_payment_for_the_owned_student(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/academies/{$academy->id}/students/{$student->id}/payments", $this->paymentPayload());

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'student_id' => (string) $student->id,
                    'subject' => 'Monthly fee',
                    'amount' => '30.00',
                ],
            ]);

        $this->assertDatabaseHas('payments', [
            'student_id' => $student->id,
            'subject' => 'Monthly fee',
        ]);
    }

    #[Test]
    public function it_shows_a_payment_of_the_owned_student(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);
        $payment = $this->createPayment($student);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/academies/{$academy->id}/students/{$student->id}/payments/{$payment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => ['id' => (string) $payment->id, 'subject' => 'Registration fee'],
            ]);
    }

    #[Test]
    public function it_returns_404_when_the_academy_belongs_to_another_user(): void
    {
        $other = User::factory()->create();
        $academy = $this->createAcademy($other);
        $student = $this->createStudent($academy);

        $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/academies/{$academy->id}/students/{$student->id}/payments")
            ->assertStatus(404)
            ->assertJson(['message' => 'Academy not found.', 'status' => 404]);

        $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/academies/{$academy->id}/students/{$student->id}/payments", $this->paymentPayload())
            ->assertStatus(404);
    }

    #[Test]
    public function it_returns_404_for_a_payment_that_belongs_to_a_different_student(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);
        $otherStudent = $this->createStudent($academy, ['name' => 'Other Student']);
        $payment = $this->createPayment($otherStudent);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/academies/{$academy->id}/students/{$student->id}/payments/{$payment->id}");

        $response->assertStatus(404)
            ->assertJson(['message' => 'Payment not found.', 'status' => 404]);
    }

    #[Test]
    public function it_returns_404_for_a_non_existent_payment(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/academies/{$academy->id}/students/{$student->id}/payments/does-not-exist");

        $response->assertStatus(404);
    }

    #[Test]
    public function it_updates_a_payment_of_the_owned_student(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);
        $payment = $this->createPayment($student);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/academies/{$academy->id}/students/{$student->id}/payments/{$payment->id}", [
                'subject' => 'Renamed Payment',
            ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['subject' => 'Renamed Payment']]);

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'subject' => 'Renamed Payment']);
    }

    #[Test]
    public function it_returns_404_when_updating_a_payment_of_a_different_student(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);
        $otherStudent = $this->createStudent($academy, ['name' => 'Other Student']);
        $payment = $this->createPayment($otherStudent);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/academies/{$academy->id}/students/{$student->id}/payments/{$payment->id}", $this->paymentPayload());

        $response->assertStatus(404);
    }

    #[Test]
    public function it_rejects_invalid_payload_on_store(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/academies/{$academy->id}/students/{$student->id}/payments", [
                'subject' => '',
                'amount' => 'not-a-number',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Validation failed.',
                'status' => 422,
            ]);
    }

    #[Test]
    public function it_deletes_a_payment_of_the_owned_student(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);
        $payment = $this->createPayment($student);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/academies/{$academy->id}/students/{$student->id}/payments/{$payment->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }

    #[Test]
    public function it_returns_404_when_deleting_a_payment_of_a_different_student(): void
    {
        $academy = $this->createAcademy($this->user);
        $student = $this->createStudent($academy);
        $otherStudent = $this->createStudent($academy, ['name' => 'Other Student']);
        $payment = $this->createPayment($otherStudent);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/academies/{$academy->id}/students/{$student->id}/payments/{$payment->id}");

        $response->assertStatus(404);
    }

    #[Test]
    public function it_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/api/v1/academies/1/students/1/payments')->assertStatus(401);
        $this->postJson('/api/v1/academies/1/students/1/payments', $this->paymentPayload())->assertStatus(401);
        $this->getJson('/api/v1/academies/1/students/1/payments/1')->assertStatus(401);
        $this->putJson('/api/v1/academies/1/students/1/payments/1', $this->paymentPayload())->assertStatus(401);
        $this->deleteJson('/api/v1/academies/1/students/1/payments/1')->assertStatus(401);
    }
}
