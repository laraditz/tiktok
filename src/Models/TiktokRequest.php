<?php

namespace Laraditz\TikTok\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class TiktokRequest extends Model
{
    use HasUlids;

    protected $fillable = ['shop_id', 'action', 'url', 'request', 'request_id', 'code', 'message', 'response', 'error'];

    protected function casts(): array
    {
        return [
            'request' => 'json',
            'response' => 'json',
        ];
    }
}
