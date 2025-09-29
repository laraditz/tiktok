<?php

namespace Laraditz\TikTok\Models;

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
