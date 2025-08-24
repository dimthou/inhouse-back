<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OAuthAuthCode extends Model
{
    use HasFactory;

    protected $table = 'oauth_auth_codes';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'client_id',
        'user_id',
        'scopes',
        'redirect_uri',
        'revoked',
        'expires_at',
    ];

    protected $casts = [
        'revoked' => 'boolean',
        'expires_at' => 'datetime',
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
     * Get the client that owns the auth code.
     */
    public function client()
    {
        return $this->belongsTo(OAuthClient::class, 'client_id');
    }

    /**
     * Get the user that owns the auth code.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the auth code is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the auth code is valid.
     */
    public function isValid(): bool
    {
        return !$this->revoked && !$this->isExpired();
    }

    /**
     * Revoke the auth code.
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
}
