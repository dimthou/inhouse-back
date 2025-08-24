<?php

namespace Tests\Unit;

use App\Models\OAuthClient;
use App\Models\OAuthAuthCode;
use App\Models\OAuthAccessToken;
use App\Models\OAuthRefreshToken;
use App\Models\User;
use App\Services\OAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private OAuthService $oauthService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->oauthService = new OAuthService();
    }

    #[Test]
    public function it_can_create_oauth_client()
    {
        $clientData = [
            'name' => 'Test Client',
            'redirect' => 'http://localhost/callback',
            'personal_access_client' => false,
            'password_client' => true,
        ];

        $client = $this->oauthService->createClient($clientData);

        $this->assertInstanceOf(OAuthClient::class, $client);
        $this->assertEquals('Test Client', $client->name);
        $this->assertEquals('http://localhost/callback', $client->redirect);
        $this->assertFalse($client->personal_access_client);
        $this->assertTrue($client->password_client);
        $this->assertNotEmpty($client->secret);
    }

    #[Test]
    public function it_can_generate_authorization_code()
    {
        $client = OAuthClient::factory()->create();
        $user = User::factory()->create();
        $scopes = ['read', 'write'];

        $authCode = $this->oauthService->generateAuthCode($client, $user, $scopes);

        $this->assertInstanceOf(OAuthAuthCode::class, $authCode);
        $this->assertEquals($client->id, $authCode->client_id);
        $this->assertEquals($user->id, $authCode->user_id);
        $this->assertEquals($scopes, $authCode->scopes);
        $this->assertEquals($client->redirect, $authCode->redirect_uri);
        $this->assertTrue($authCode->expires_at->isFuture());
    }

    #[Test]
    public function it_can_exchange_auth_code_for_token()
    {
        $client = OAuthClient::factory()->create(['secret' => 'test-secret']);
        $user = User::factory()->create();
        $authCode = OAuthAuthCode::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
            'revoked' => false,
            'expires_at' => now()->addMinutes(5),
        ]);

        $result = $this->oauthService->exchangeAuthCodeForToken(
            $authCode->id,
            $client->id,
            'test-secret'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('token_type', $result);
        $this->assertEquals('Bearer', $result['token_type']);
        $this->assertEquals(3600, $result['expires_in']);

        // Check that auth code was revoked
        $this->assertTrue($authCode->fresh()->revoked);
    }

    #[Test]
    public function it_returns_null_for_expired_auth_code()
    {
        $client = OAuthClient::factory()->create();
        $user = User::factory()->create();
        $authCode = OAuthAuthCode::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
            'revoked' => false,
            'expires_at' => now()->subMinutes(5), // Expired
        ]);

        $result = $this->oauthService->exchangeAuthCodeForToken(
            $authCode->id,
            $client->id
        );

        $this->assertNull($result);
    }

    #[Test]
    public function it_can_create_password_token()
    {
        $client = OAuthClient::factory()->create();
        $user = User::factory()->create();
        $scopes = ['read', 'write'];

        $result = $this->oauthService->createPasswordToken($client, $user, $scopes);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('token_type', $result);
        $this->assertEquals('Bearer', $result['token_type']);
        $this->assertEquals(3600, $result['expires_in']);
    }

    #[Test]
    public function it_can_validate_access_token()
    {
        $client = OAuthClient::factory()->create();
        $user = User::factory()->create();
        $accessToken = OAuthAccessToken::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
            'revoked' => false,
            'expires_at' => now()->addHour(),
        ]);

        $validated = $this->oauthService->validateAccessToken($accessToken->id);

        $this->assertInstanceOf(OAuthAccessToken::class, $validated);
        $this->assertEquals($accessToken->id, $validated->id);
    }

    #[Test]
    public function it_returns_null_for_invalid_access_token()
    {
        $result = $this->oauthService->validateAccessToken('invalid-token');

        $this->assertNull($result);
    }

    #[Test]
    public function it_can_revoke_access_token()
    {
        $client = OAuthClient::factory()->create();
        $user = User::factory()->create();
        $accessToken = OAuthAccessToken::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
            'revoked' => false,
        ]);

        $result = $this->oauthService->revokeAccessToken($accessToken->id);

        $this->assertTrue($result);
        $this->assertTrue($accessToken->fresh()->revoked);
    }

    #[Test]
    public function it_returns_false_for_nonexistent_token_revocation()
    {
        $result = $this->oauthService->revokeAccessToken('nonexistent-token');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_returns_available_scopes()
    {
        $scopes = $this->oauthService->getAvailableScopes();

        $expectedScopes = [
            'read' => 'Read access to resources',
            'write' => 'Write access to resources',
            'delete' => 'Delete access to resources',
            'admin' => 'Administrative access',
        ];

        $this->assertEquals($expectedScopes, $scopes);
    }
}
