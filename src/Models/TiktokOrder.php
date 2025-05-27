<?php

namespace Laraditz\TikTok\Models;

use Illuminate\Database\Eloquent\Model;

class TiktokOrder extends Model
{
    public $incrementing = false;

    protected $fillable = ['id', 'shop_id', 'status', 'is_on_hold_order', 'update_time'];

    protected function casts(): array
    {
        return [
            'is_on_hold_order' => 'boolean',
        ];
    }

    public function shop()
    {
        return $this->belongsTo(TiktokShop::class, 'identifier', 'shop_id');
    }
}
