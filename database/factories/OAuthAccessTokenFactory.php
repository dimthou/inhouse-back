<?php

namespace Database\Factories;

use App\Models\OAuthAccessToken;
use App\Models\OAuthClient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OAuthAccessToken>
 */
class OAuthAccessTokenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OAuthAccessToken::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::random(100),
            'client_id' => OAuthClient::factory(),
            'user_id' => User::factory(),
            'scopes' => ['read', 'write'],
            'revoked' => false,
            'expires_at' => now()->addHour(),
        ];
    }

    /**
     * Indicate that the access token is revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked' => true,
        ]);
    }

    /**
     * Indicate that the access token is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHour(),
        ]);
    }

    /**
     * Create an access token without a user (for client credentials grant).
     */
    public function clientCredentials(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }
}
