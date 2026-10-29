<?php

namespace Laraditz\TikTok\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class TiktokWebhook extends Model
{
    use MassPrunable;

    protected $fillable = ['shop_id', 'type_id', 'event_type', 'event_data'];

    protected function casts(): array
    {
        return [
            'event_data' => 'json',
        ];
    }

    public function shop()
    {
        return $this->belongsTo(TiktokShop::class);
    }

    public function prunable(): Builder
    {
        $days = config('tiktok.logging.prune_after_days');

        if (is_numeric($days) && (int) $days > 0) {
            return static::where('created_at', '<=', now()->subDays((int) $days));
        }

        return static::query()->whereRaw('1 = 0');
    }
}
