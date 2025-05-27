<?php

namespace Laraditz\TikTok\Models;

use Illuminate\Database\Eloquent\Model;

class TiktokReturnOrder extends Model
{
    protected $fillable = [
        'shop_id',
        'order_id',
        'role',
        'type',
        'status',
        'return_id',
        'create_time',
        'update_time',
    ];
}
