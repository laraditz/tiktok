<?php

namespace Laraditz\TikTok\Models;

use Laraditz\TikTok\Models\TiktokShop;
use Illuminate\Database\Eloquent\Model;

class TiktokEventWebhook extends Model
{
    protected $fillable = [
        'shop_id',
        'event_type',
        'address',
    ];

    public function shop()
    {
        return $this->belongsTo(TiktokShop::class, 'shop_id');
    }
}
