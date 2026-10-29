<?php

namespace Laraditz\TikTok\Models;

use Laraditz\TikTok\Casts\EncryptedToken;
use Illuminate\Database\Eloquent\Model;

class TiktokShop extends Model
{
    protected $fillable = [
        'id',
        'code',
        'name',
        'region',
        'seller_type',
        'cipher',
        'open_id',
    ];

    protected function casts(): array
    {
        return [
            'cipher' => EncryptedToken::class,
        ];
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    public function accessToken()
    {
        return $this->morphOne(TiktokAccessToken::class, 'subjectable');
    }
}
