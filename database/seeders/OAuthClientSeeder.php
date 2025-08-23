<?php

namespace Database\Seeders;

use App\Models\OAuthClient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OAuthClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a password client for testing
        OAuthClient::create([
            'name' => 'InHouse Password Client',
            'secret' => Str::random(40),
            'redirect' => 'http://localhost:3000/callback',
            'personal_access_client' => false,
            'password_client' => true,
            'revoked' => false,
        ]);

        // Create a client credentials client for testing
        OAuthClient::create([
            'name' => 'InHouse Client Credentials Client',
            'secret' => Str::random(40),
            'redirect' => 'http://localhost:3000/callback',
            'personal_access_client' => false,
            'password_client' => false,
            'revoked' => false,
        ]);

        // Create a public client for authorization code flow
        OAuthClient::create([
            'name' => 'InHouse Public Client',
            'secret' => null, // Public client
            'redirect' => 'http://localhost:3000/callback',
            'personal_access_client' => false,
            'password_client' => false,
            'revoked' => false,
        ]);
    }
}
