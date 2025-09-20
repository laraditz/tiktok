<?php

namespace Laraditz\TikTok\Models;

use Laraditz\TikTok\Enums\UserType;
use Illuminate\Database\Eloquent\Model;

class TiktokAccessToken extends Model
{
    protected $fillable = [
        'subjectable_type',
        'subjectable_id',
        'access_token',
        'refresh_token',
        'expires_at',
        'refresh_expires_at',
        'open_id',
        'seller_name',
        'seller_base_region',
        'user_type',
        'granted_scopes',
        'code'
    ];

    protected function casts(): array
    {
        return [
            'granted_scopes' => 'json',
            'expires_at' => 'datetime',
            'refresh_expires_at' => 'datetime',
            'user_type' => UserType::class,
        ];
    }

    public function subjectable()
    {
        return $this->morphTo();
    }
}
