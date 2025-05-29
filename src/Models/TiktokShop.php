<?php

namespace Laraditz\TikTok\Models;

use Illuminate\Database\Eloquent\Model;

class TiktokShop extends Model
{
    protected $fillable = [
        'identifier',
        'code',
        'name',
        'region',
        'seller_type',
        'cipher',
        'open_id',
    ];

    public function accessToken()
    {
        return $this->morphOne(TiktokAccessToken::class, 'subjectable');
    }
}
