<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OAuthClient extends Model
{
    use HasFactory;

    protected $table = 'oauth_clients';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'secret',
        'provider',
        'redirect',
        'personal_access_client',
        'password_client',
        'revoked',
    ];

    protected $casts = [
        'personal_access_client' => 'boolean',
        'password_client' => 'boolean',
        'revoked' => 'boolean',
    ];

    protected $hidden = [
        'secret',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid();
            }
        });
    }

    /**
     * Get the user that owns the client.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the access tokens for the client.
     */
    public function accessTokens()
    {
        return $this->hasMany(OAuthAccessToken::class, 'client_id');
    }

    /**
     * Get the auth codes for the client.
     */
    public function authCodes()
    {
        return $this->hasMany(OAuthAuthCode::class, 'client_id');
    }

    /**
     * Check if the client is confidential.
     */
    public function confidential()
    {
        return !empty($this->secret);
    }

    /**
     * Check if the client is public.
     */
    public function public()
    {
        return !$this->confidential();
    }

    /**
     * Check if the client is first party.
     */
    public function firstParty()
    {
        return $this->personal_access_client || $this->password_client;
    }

    /**
     * Check if the client is third party.
     */
    public function thirdParty()
    {
        return !$this->firstParty();
    }
}
