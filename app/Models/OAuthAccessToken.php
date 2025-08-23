<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OAuthAccessToken extends Model
{
    use HasFactory;

    protected $table = 'oauth_access_tokens';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'client_id',
        'user_id',
        'scopes',
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
     * Get the client that owns the access token.
     */
    public function client()
    {
        return $this->belongsTo(OAuthClient::class, 'client_id');
    }

    /**
     * Get the user that owns the access token.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the refresh token for this access token.
     */
    public function refreshToken()
    {
        return $this->hasOne(OAuthRefreshToken::class, 'access_token_id');
    }

    /**
     * Check if the access token is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the access token is valid.
     */
    public function isValid(): bool
    {
        return !$this->revoked && !$this->isExpired();
    }

    /**
     * Revoke the access token.
     */
    public function revoke(): void
    {
        $this->update(['revoked' => true]);
    }

    /**
     * Get the scopes as an array.
     */
    public function getScopesAttribute($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Set the scopes as JSON.
     */
    public function setScopesAttribute($value): void
    {
        $this->attributes['scopes'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Check if the token has a specific scope.
     */
    public function can($scope): bool
    {
        return in_array($scope, $this->scopes);
    }

    /**
     * Check if the token has any of the given scopes.
     */
    public function canAny(array $scopes): bool
    {
        return !empty(array_intersect($scopes, $this->scopes));
    }
}
