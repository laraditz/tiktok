<?php

namespace Laraditz\TikTok\Models;

use Illuminate\Database\Eloquent\Model;

class TiktokWebhook extends Model
{
    protected $fillable = ['shop_id', 'type_id', 'event_type', 'event_data'];

    protected function casts(): array
    {
        return [
            'event_data' => 'json',
        ];
    }

    public function shop()
    {
        return $this->belongsTo(TiktokShop::class, 'identifier', 'shop_id');
    }
}
