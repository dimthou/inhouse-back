<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\RefreshToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function user_can_register_with_valid_data()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'created_at',
                            'updated_at',
                        ],
                        'access_token',
                        'refresh_token',
                        'token_type',
                        'expires_in',
                    ]
                ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'api',
        ]);

        $this->assertDatabaseHas('refresh_tokens', [
            'user_id' => User::where('email', 'john@example.com')->first()->id,
        ]);
    }

    #[Test]
    public function user_cannot_register_with_invalid_data()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '456',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    #[Test]
    public function user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'user',
                        'access_token',
                        'refresh_token',
                        'token_type',
                        'expires_in',
                    ]
                ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'api',
            'tokenable_id' => $user->id,
        ]);
    }

    #[Test]
    public function user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ]);
    }

    #[Test]
    public function user_can_refresh_token_with_valid_refresh_token()
    {
        $user = User::factory()->create();
        $refreshToken = RefreshToken::factory()->create([
            'user_id' => $user->id,
            'is_revoked' => false,
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken->token,
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'access_token',
                        'refresh_token',
                        'token_type',
                        'expires_in',
                    ]
                ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'api',
            'tokenable_id' => $user->id,
        ]);
    }

    #[Test]
    public function user_cannot_refresh_token_with_invalid_refresh_token()
    {
        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => 'invalid-token',
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid or expired refresh token',
                ]);
    }

    #[Test]
    public function user_can_logout()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Logged out successfully',
                ]);

        // Check that the current token was deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'api',
        ]);
    }

    #[Test]
    public function user_can_logout_all_sessions()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create multiple tokens
        $user->createToken('api');
        $user->createToken('mobile');

        $response = $this->postJson('/api/auth/logout-all');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'All sessions logged out successfully',
                ]);

        // Check that all tokens were deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_protected_endpoints()
    {
        $response = $this->postJson('/api/auth/logout');
        $response->assertStatus(401);

        $response = $this->postJson('/api/auth/logout-all');
        $response->assertStatus(401);
    }
}
