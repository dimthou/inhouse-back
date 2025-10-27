<?php

namespace App\Services;

use App\Models\OAuthClient;
use App\Models\OAuthAuthCode;
use App\Models\OAuthAccessToken;
use App\Models\OAuthRefreshToken;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OAuthService
{
    /**
     * Create a new OAuth client.
     */
    public function createClient(array $data): OAuthClient
    {
        return OAuthClient::create([
            'name' => $data['name'],
            'secret' => $data['secret'] ?? Str::random(40),
            'redirect' => $data['redirect'],
            'personal_access_client' => $data['personal_access_client'] ?? false,
            'password_client' => $data['password_client'] ?? false,
            'user_id' => $data['user_id'] ?? null,
        ]);
    }

    /**
     * Generate authorization code for authorization code flow.
     */
    public function generateAuthCode(OAuthClient $client, User $user, array $scopes = [], string $redirectUri = null): OAuthAuthCode
    {
        return OAuthAuthCode::create([
            'client_id' => $client->id,
            'user_id' => $user->id,
            'scopes' => $scopes,
            'redirect_uri' => $redirectUri ?? $client->redirect,
            'expires_at' => Carbon::now()->addMinutes(10), // 10 minutes expiry
        ]);
    }

    /**
     * Exchange authorization code for access token.
     */
    public function exchangeAuthCodeForToken(string $authCode, string $clientId, string $clientSecret = null): ?array
    {
        $authCodeModel = OAuthAuthCode::where('id', $authCode)
            ->where('client_id', $clientId)
            ->where('revoked', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$authCodeModel) {
            return null;
        }

        // Verify client secret for confidential clients
        $client = OAuthClient::find($clientId);
        if ($client->confidential() && !\Illuminate\Support\Facades\Hash::check($clientSecret ?? '', $client->secret)) {
            return null;
        }

        // Revoke the auth code
        $authCodeModel->revoke();

        // Create access token
        $accessToken = $this->createAccessToken($client, $authCodeModel->user, $authCodeModel->scopes);

        return [
            'access_token' => $accessToken->id,
            'token_type' => 'Bearer',
            'expires_in' => 3600, // 1 hour
            'refresh_token' => $accessToken->refreshToken->id,
            'scope' => implode(' ', $accessToken->scopes),
        ];
    }

    /**
     * Create access token for password grant.
     */
    public function createPasswordToken(OAuthClient $client, User $user, array $scopes = []): array
    {
        $accessToken = $this->createAccessToken($client, $user, $scopes);

        return [
            'access_token' => $accessToken->id,
            'token_type' => 'Bearer',
            'expires_in' => 3600, // 1 hour
            'refresh_token' => $accessToken->refreshToken->id,
            'scope' => implode(' ', $accessToken->scopes),
        ];
    }

    /**
     * Create access token for client credentials grant.
     */
    public function createClientCredentialsToken(OAuthClient $client, array $scopes = []): array
    {
        $accessToken = $this->createAccessToken($client, null, $scopes);

        return [
            'access_token' => $accessToken->id,
            'token_type' => 'Bearer',
            'expires_in' => 3600, // 1 hour
            'scope' => implode(' ', $accessToken->scopes),
        ];
    }

    /**
     * Refresh access token.
     */
    public function refreshToken(string $refreshToken, string $clientId, string $clientSecret = null): ?array
    {
        $refreshTokenModel = OAuthRefreshToken::where('id', $refreshToken)
            ->where('revoked', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$refreshTokenModel) {
            return null;
        }

        $accessToken = $refreshTokenModel->accessToken;
        $client = $accessToken->client;

        // Verify client secret for confidential clients
        if ($client->confidential() && !\Illuminate\Support\Facades\Hash::check($clientSecret ?? '', $client->secret)) {
            return null;
        }

        // Revoke old tokens
        $accessToken->revoke();
        $refreshTokenModel->revoke();

        // Create new tokens
        $newAccessToken = $this->createAccessToken($client, $accessToken->user, $accessToken->scopes);

        return [
            'access_token' => $newAccessToken->id,
            'token_type' => 'Bearer',
            'expires_in' => 3600, // 1 hour
            'refresh_token' => $newAccessToken->refreshToken->id,
            'scope' => implode(' ', $newAccessToken->scopes),
        ];
    }

    /**
     * Create access token with refresh token.
     */
    private function createAccessToken(OAuthClient $client, ?User $user, array $scopes = []): OAuthAccessToken
    {
        $accessToken = OAuthAccessToken::create([
            'client_id' => $client->id,
            'user_id' => $user?->id,
            'scopes' => $scopes,
            'expires_at' => Carbon::now()->addHour(), // 1 hour expiry
        ]);

        // Create refresh token
        OAuthRefreshToken::create([
            'access_token_id' => $accessToken->id,
            'expires_at' => Carbon::now()->addDays(30), // 30 days expiry
        ]);

        return $accessToken;
    }

    /**
     * Validate access token.
     */
    public function validateAccessToken(string $token): ?OAuthAccessToken
    {
        return OAuthAccessToken::where('id', $token)
            ->where('revoked', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    /**
     * Revoke access token.
     */
    public function revokeAccessToken(string $token): bool
    {
        $accessToken = OAuthAccessToken::where('id', $token)->first();
        
        if ($accessToken) {
            $accessToken->revoke();
            if ($accessToken->refreshToken) {
                $accessToken->refreshToken->revoke();
            }
            return true;
        }

        return false;
    }

    /**
     * Get available scopes.
     */
    public function getAvailableScopes(): array
    {
        return [
            'read' => 'Read access to resources',
            'write' => 'Write access to resources',
            'delete' => 'Delete access to resources',
            'admin' => 'Administrative access',
        ];
    }
}
