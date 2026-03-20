<?php

namespace Laraditz\TikTok\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TiktokProductSku extends Model
{
    use SoftDeletes;

    protected $fillable = ['id', 'product_id', 'seller_sku', 'inventory', 'price', 'status_info'];

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    protected function casts(): array
    {
        return [
            'inventory' => 'json',
            'price' => 'json',
            'status_info' => 'json',
        ];
    }

    public function product()
    {
        return $this->belongsTo(TiktokProduct::class);
    }
}
