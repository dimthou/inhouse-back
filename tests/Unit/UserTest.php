<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\RefreshToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_a_user()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertTrue($user->exists);
    }

    #[Test]
    public function it_has_fillable_attributes()
    {
        $user = new User();
        
        $expectedFillable = ['name', 'email', 'password'];
        $this->assertEquals($expectedFillable, $user->getFillable());
    }

    #[Test]
    public function it_has_hidden_attributes()
    {
        $user = new User();
        
        $expectedHidden = ['password', 'remember_token'];
        $this->assertEquals($expectedHidden, $user->getHidden());
    }

    #[Test]
    public function it_has_proper_casts()
    {
        $user = new User();
        
        $expectedCasts = [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
        
        // Check that expected casts exist (Laravel may add additional casts)
        foreach ($expectedCasts as $key => $value) {
            $this->assertArrayHasKey($key, $user->getCasts());
            $this->assertEquals($value, $user->getCasts()[$key]);
        }
    }

    #[Test]
    public function it_can_have_refresh_tokens()
    {
        $user = User::factory()->create();
        $refreshToken = RefreshToken::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(RefreshToken::class, $user->refreshTokens->first());
        $this->assertEquals($refreshToken->id, $user->refreshTokens->first()->id);
    }

    #[Test]
    public function it_can_revoke_all_tokens()
    {
        $user = User::factory()->create();
        
        // Create some Sanctum tokens
        $token1 = $user->createToken('test-token-1');
        $token2 = $user->createToken('test-token-2');
        
        // Create some refresh tokens
        $refreshToken1 = RefreshToken::factory()->create(['user_id' => $user->id]);
        $refreshToken2 = RefreshToken::factory()->create(['user_id' => $user->id]);

        $this->assertEquals(2, $user->tokens()->count());
        $this->assertEquals(2, $user->refreshTokens()->count());

        // Revoke all tokens
        $user->revokeAllTokens();

        // Check that Sanctum tokens are deleted
        $this->assertEquals(0, $user->tokens()->count());
        
        // Check that refresh tokens are revoked
        $this->assertTrue($user->refreshTokens()->first()->is_revoked);
    }

    #[Test]
    public function it_uses_required_traits()
    {
        $user = new User();
        
        // Check that the user has the required traits by checking for their methods
        $this->assertTrue(method_exists($user, 'createToken')); // From HasApiTokens
        $this->assertTrue(method_exists($user, 'notify')); // From Notifiable
        $this->assertTrue(method_exists($user, 'newFactory')); // From HasFactory
    }
}
