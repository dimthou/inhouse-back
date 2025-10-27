<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RefreshToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'access_token_id',
        'expires_at',
        'is_revoked',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_revoked' => 'boolean',
    ];

    protected $hidden = [
        'token',
    ];

    /**
     * Get the user that owns the refresh token.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a new refresh token.
     */
    public static function generateToken(User $user, string $accessTokenId): self
    {
        return self::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'access_token_id' => $accessTokenId,
            'expires_at' => Carbon::now()->addDays(30), // 30 days expiry
        ]);
    }

    /**
     * Check if the token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the token is valid (not expired and not revoked).
     */
    public function isValid(): bool
    {
        return !$this->is_revoked && !$this->isExpired();
    }

    /**
     * Revoke the token.
     */
    public function revoke(): void
    {
        $this->update(['is_revoked' => true]);
    }

    /**
     * Find a valid refresh token by token string.
     */
    public static function findValidToken(string $token): ?self
    {
        return self::where('token', $token)
            ->where('is_revoked', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }
}
