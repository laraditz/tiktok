<?php

namespace Laraditz\TikTok\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TiktokProduct extends Model
{
    use SoftDeletes;

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

    public function shop(): BelongsTo
    {
        return $this->belongsTo(TiktokShop::class);
    }

    public function skus(): HasMany
    {
        return $this->hasMany(TiktokProductSku::class, 'product_id', 'id');
    }
}
