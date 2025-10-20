<?php

namespace App\Http\Controllers;

use App\Http\Requests\OAuth\AuthorizationRequest;
use App\Http\Requests\OAuth\TokenRequest;
use App\Http\Requests\OAuth\ClientRequest;
use App\Models\OAuthClient;
use App\Models\User;
use App\Services\OAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class OAuthController extends Controller
{
    public function __construct(
        private OAuthService $oauthService
    ) {}

    /**
     * Authorization endpoint for authorization code flow.
     */
    public function authorize(AuthorizationRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        $client = OAuthClient::find($data['client_id']);
        if (!$client || $client->revoked) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Client not found or revoked'
            ], 400);
        }

        // For authorization code flow, redirect to consent page
        // In a real app, you'd redirect to a consent page
        // For now, we'll generate the auth code directly
        
        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'error' => 'invalid_credentials',
                'error_description' => 'Invalid email or password'
            ], 401);
        }

        $scopes = $data['scope'] ? explode(' ', $data['scope']) : ['read'];
        $authCode = $this->oauthService->generateAuthCode($client, $user, $scopes, $data['redirect_uri'] ?? null);

        return response()->json([
            'success' => true,
            'authorization_code' => $authCode->id,
            'expires_in' => 600, // 10 minutes
            'redirect_uri' => $data['redirect_uri'] ?? $client->redirect,
        ]);
    }

    /**
     * Token endpoint for all OAuth 2.0 grant types.
     */
    public function token(TokenRequest $request): JsonResponse
    {
        $data = $request->validated();
        $grantType = $data['grant_type'];

        switch ($grantType) {
            case 'authorization_code':
                return $this->handleAuthorizationCodeGrant($data);
            
            case 'password':
                return $this->handlePasswordGrant($data);
            
            case 'client_credentials':
                return $this->handleClientCredentialsGrant($data);
            
            case 'refresh_token':
                return $this->handleRefreshTokenGrant($data);
            
            default:
                return response()->json([
                    'error' => 'unsupported_grant_type',
                    'error_description' => 'Grant type not supported'
                ], 400);
        }
    }

    /**
     * Handle authorization code grant.
     */
    private function handleAuthorizationCodeGrant(array $data): JsonResponse
    {
        $client = OAuthClient::find($data['client_id']);
        if (!$client || $client->revoked) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Client not found or revoked'
            ], 400);
        }
        
        $tokenData = $this->oauthService->exchangeAuthCodeForToken(
            $data['code'],
            $data['client_id'],
            $data['client_secret'] ?? null
        );

        if (!$tokenData) {
            return response()->json([
                'error' => 'invalid_grant',
                'error_description' => 'Invalid authorization code'
            ], 400);
        }

        return response()->json($tokenData);
    }

    /**
     * Handle password grant.
     */
    private function handlePasswordGrant(array $data): JsonResponse
    {
        $client = OAuthClient::find($data['client_id']);
        if (!$client || $client->revoked || !$client->password_client) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Client not found, revoked, or not authorized for password grant'
            ], 400);
        }

        $user = User::where('email', $data['username'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'error' => 'invalid_grant',
                'error_description' => 'Invalid credentials'
            ], 400);
        }

        $scopes = $data['scope'] ? explode(' ', $data['scope']) : ['read'];
        $tokenData = $this->oauthService->createPasswordToken($client, $user, $scopes);

        return response()->json($tokenData);
    }

    /**
     * Handle client credentials grant.
     */
    private function handleClientCredentialsGrant(array $data): JsonResponse
    {
        $client = OAuthClient::find($data['client_id']);
        if (!$client || $client->revoked) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Client not found or revoked'
            ], 400);
        }

        // Verify client secret for confidential clients
        if ($client->confidential() && !Hash::check($data['client_secret'] ?? '', $client->secret)) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Invalid client secret'
            ], 400);
        }

        $scopes = $data['scope'] ? explode(' ', $data['scope']) : ['read'];
        $tokenData = $this->oauthService->createClientCredentialsToken($client, $scopes);

        return response()->json($tokenData);
    }

    /**
     * Handle refresh token grant.
     */
    private function handleRefreshTokenGrant(array $data): JsonResponse
    {
        $client = OAuthClient::find($data['client_id']);
        if (!$client || $client->revoked) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Client not found or revoked'
            ], 400);
        }

        $tokenData = $this->oauthService->refreshToken(
            $data['refresh_token'],
            $data['client_id'],
            $data['client_secret'] ?? null
        );

        if (!$tokenData) {
            return response()->json([
                'error' => 'invalid_grant',
                'error_description' => 'Invalid refresh token'
            ], 400);
        }

        return response()->json($tokenData);
    }

    /**
     * Create a new OAuth client.
     */
    public function createClient(ClientRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        $client = $this->oauthService->createClient($data);

        return response()->json([
            'success' => true,
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'secret' => $client->secret,
                'redirect' => $client->redirect,
                'personal_access_client' => $client->personal_access_client,
                'password_client' => $client->password_client,
            ]
        ], 201);
    }

    /**
     * Get available scopes.
     */
    public function scopes(): JsonResponse
    {
        return response()->json([
            'scopes' => $this->oauthService->getAvailableScopes()
        ]);
    }

    /**
     * Revoke access token.
     */
    public function revoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'client_id' => 'required|string',
            'client_secret' => 'required_if:client_confidential,true|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'invalid_request',
                'error_description' => 'Missing required parameters'
            ], 400);
        }

        $client = OAuthClient::find($request->client_id);
        if (!$client || $client->revoked) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Client not found or revoked'
            ], 400);
        }

        // Verify client secret for confidential clients
        if ($client->confidential() && !Hash::check($request->client_secret ?? '', $client->secret)) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Invalid client secret'
            ], 400);
        }

        $revoked = $this->oauthService->revokeAccessToken($request->token);

        if ($revoked) {
            return response()->json(['success' => true]);
        } else {
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'Token not found'
            ], 400);
        }
    }

    /**
     * Get token info.
     */
    public function tokenInfo(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'No token provided'
            ], 400);
        }

        $accessToken = $this->oauthService->validateAccessToken($token);
        if (!$accessToken) {
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'Token is invalid or expired'
            ], 400);
        }

        return response()->json([
            'active' => true,
            'client_id' => $accessToken->client_id,
            'user_id' => $accessToken->user_id,
            'scope' => implode(' ', $accessToken->scopes),
            'exp' => $accessToken->expires_at->timestamp,
        ]);
    }
}
