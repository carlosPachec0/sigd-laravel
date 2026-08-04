<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Domain\Entities\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => Hash::make('CurrentPassword1!'),
        ]);
    }

    #[Test]
    public function it_changes_the_password_with_correct_current_password(): void
    {
        $token = $this->user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'CurrentPassword1!',
                'new_password' => 'NewPassword1!',
                'new_password_confirmation' => 'NewPassword1!',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Password changed successfully.',
                'status' => 200,
            ]);

        $this->assertTrue(Hash::check('NewPassword1!', $this->user->fresh()->password));
    }

    #[Test]
    public function it_rejects_an_incorrect_current_password(): void
    {
        $token = $this->user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'WrongPassword1!',
                'new_password' => 'NewPassword1!',
                'new_password_confirmation' => 'NewPassword1!',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'The provided current password is incorrect.',
                'status' => 422,
            ]);
    }

    #[Test]
    public function it_revokes_other_tokens_but_keeps_the_current_one(): void
    {
        $currentToken = $this->user->createToken('current-device');
        $otherToken = $this->user->createToken('other-device');

        $this->withHeader('Authorization', "Bearer {$currentToken->plainTextToken}")
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'CurrentPassword1!',
                'new_password' => 'NewPassword1!',
                'new_password_confirmation' => 'NewPassword1!',
            ])->assertStatus(200);

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $currentToken->accessToken->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $otherToken->accessToken->id]);
    }

    #[Test]
    public function it_rejects_a_weak_new_password(): void
    {
        $token = $this->user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'CurrentPassword1!',
                'new_password' => '123',
                'new_password_confirmation' => '123',
            ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_rejects_a_new_password_matching_the_current_one(): void
    {
        $token = $this->user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'CurrentPassword1!',
                'new_password' => 'CurrentPassword1!',
                'new_password_confirmation' => 'CurrentPassword1!',
            ]);

        $response->assertStatus(422);
    }
}
