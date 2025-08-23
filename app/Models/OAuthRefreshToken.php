<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OAuthRefreshToken extends Model
{
    use HasFactory;

    protected $table = 'oauth_refresh_tokens';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'access_token_id',
        'revoked',
        'expires_at',
    ];

    protected $casts = [
        'revoked' => 'boolean',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::random(40);
            }
        });
    }

    /**
     * Get the access token that owns the refresh token.
     */
    public function accessToken()
    {
        return $this->belongsTo(OAuthAccessToken::class, 'access_token_id');
    }

    /**
     * Check if the refresh token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the refresh token is valid.
     */
    public function isValid(): bool
    {
        return !$this->revoked && !$this->isExpired();
    }

    /**
     * Revoke the refresh token.
     */
    public function revoke(): void
    {
        $this->update(['revoked' => true]);
    }
}
