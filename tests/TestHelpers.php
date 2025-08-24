<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\TestResponse;

trait TestHelpers
{
    use RefreshDatabase, WithFaker;

    /**
     * Assert that the response has the expected JSON structure.
     */
    protected function assertJsonStructure(TestResponse $response, array $structure, array $responseData = null): void
    {
        $responseData = $responseData ?? $response->json();
        
        foreach ($structure as $key => $value) {
            if (is_array($value) && is_string($key)) {
                $this->assertArrayHasKey($key, $responseData);
                $this->assertJsonStructure($response, $value, $responseData[$key]);
            } elseif (is_array($value)) {
                $this->assertIsArray($responseData);
                foreach ($responseData as $responseDataItem) {
                    $this->assertJsonStructure($response, $value, $responseDataItem);
                }
            } else {
                $this->assertArrayHasKey($value, $responseData);
            }
        }
    }

    /**
     * Create a user with default attributes.
     */
    protected function createUser(array $attributes = []): \App\Models\User
    {
        return \App\Models\User::factory()->create($attributes);
    }

    /**
     * Create an OAuth client with default attributes.
     */
    protected function createOAuthClient(array $attributes = []): \App\Models\OAuthClient
    {
        return \App\Models\OAuthClient::factory()->create($attributes);
    }

    /**
     * Create an inventory item with default attributes.
     */
    protected function createInventory(array $attributes = []): \App\Models\Inventory
    {
        return \App\Models\Inventory::factory()->create($attributes);
    }

    /**
     * Assert that a model has the expected attributes.
     */
    protected function assertModelHasAttributes($model, array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->assertEquals($value, $model->$key);
        }
    }

    /**
     * Assert that a model exists in the database.
     */
    protected function assertModelExists($model): void
    {
        $this->assertDatabaseHas($model->getTable(), ['id' => $model->id]);
    }

    /**
     * Assert that a model doesn't exist in the database.
     */
    protected function assertModelMissing($model): void
    {
        $this->assertDatabaseMissing($model->getTable(), ['id' => $model->id]);
    }
}
