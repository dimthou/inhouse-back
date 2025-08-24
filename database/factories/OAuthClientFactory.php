<?php

namespace Database\Factories;

use App\Models\OAuthClient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OAuthClient>
 */
class OAuthClientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OAuthClient::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'name' => $this->faker->company(),
            'secret' => Str::random(40),
            'redirect' => $this->faker->url(),
            'personal_access_client' => false,
            'password_client' => true,
            'revoked' => false,
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the client is a personal access client.
     */
    public function personalAccessClient(): static
    {
        return $this->state(fn (array $attributes) => [
            'personal_access_client' => true,
            'password_client' => false,
        ]);
    }

    /**
     * Indicate that the client is a password client.
     */
    public function passwordClient(): static
    {
        return $this->state(fn (array $attributes) => [
            'password_client' => true,
            'personal_access_client' => false,
        ]);
    }

    /**
     * Indicate that the client is revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked' => true,
        ]);
    }
}
