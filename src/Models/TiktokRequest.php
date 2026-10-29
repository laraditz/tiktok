<?php

namespace Laraditz\TikTok\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class TiktokRequest extends Model
{
    use HasUlids, MassPrunable;

    protected $fillable = ['shop_id', 'action', 'url', 'request', 'request_id', 'code', 'message', 'response', 'error'];

    protected function casts(): array
    {
        return [
            'request' => 'json',
            'response' => 'json',
        ];
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
