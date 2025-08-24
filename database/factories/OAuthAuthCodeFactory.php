<?php

namespace Database\Factories;

use App\Models\OAuthAuthCode;
use App\Models\OAuthClient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OAuthAuthCode>
 */
class OAuthAuthCodeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OAuthAuthCode::class;

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
            'redirect_uri' => $this->faker->url(),
            'revoked' => false,
            'expires_at' => now()->addMinutes(10),
        ];
    }

    /**
     * Indicate that the auth code is revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked' => true,
        ]);
    }

    /**
     * Indicate that the auth code is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinutes(5),
        ]);
    }
}
