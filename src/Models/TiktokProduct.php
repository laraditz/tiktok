<?php

namespace Laraditz\TikTok\Models;

use Illuminate\Database\Eloquent\Model;

class TiktokProduct extends Model
{
    protected $fillable = ['id', 'shop_id', 'title', 'status', 'has_draft', 'is_not_for_sale', 'sales_regions', 'audit', 'create_time', 'update_time'];

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
            'has_draft' => 'boolean',
            'is_not_for_sale' => 'boolean',
            'sales_regions' => 'json',
            'audit' => 'json',
            'create_time' => 'timestamp',
            'update_time' => 'timestamp',
        ];
    }

    public function shop()
    {
        return $this->belongsTo(TiktokShop::class);
    }
}
