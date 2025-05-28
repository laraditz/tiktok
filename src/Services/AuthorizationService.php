<?php

namespace Laraditz\TikTok\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laraditz\TikTok\Models\TiktokShop;
use Illuminate\Database\Eloquent\Builder;
use Laraditz\TikTok\Models\TiktokRequest;
use Laraditz\TikTok\Exceptions\TikTokTokenException;

class AuthorizationService extends BaseService
{
    public function afterShopsRequest(TiktokRequest $request, array $result = []): void
    {
        $code = data_get($result, 'code');
        $message = data_get($result, 'message');

        if ($code === 0 || $message === 'Success') {
            $shops = data_get($result, 'data.shops');

            if ($shops && is_array($shops) && count($shops) > 0) {
                foreach ($shops as $shop) {
                    $shop_id = data_get($shop, 'id');
                    $shop_code = data_get($shop, 'code');
                    $shop_name = data_get($shop, 'name');

                    $tikTokShop = TiktokShop::query()
                        ->where(function (Builder $query) use ($shop_id, $shop_code, $shop_name) {
                            $query->where('name', $shop_name)
                                ->orWhere('identifier', $shop_id)
                                ->orWhere('code', $shop_code);
                        });

                    if (!$tikTokShop) {
                        TiktokShop::create([
                            'identifier' => $shop_id,
                            'code' => $shop_code,
                            'name' => $shop_name,
                            'region' => data_get($shop, 'region'),
                            'seller_type' => data_get($shop, 'seller_type'),
                            'cipher' => data_get($shop, 'cipher'),
                        ]);
                    } else {
                        $tikTokShop->update([
                            'identifier' => $shop_id,
                            'name' => $shop_name,
                            'code' => $shop_code,
                            'region' => data_get($shop, 'region'),
                            'seller_type' => data_get($shop, 'seller_type'),
                            'cipher' => data_get($shop, 'cipher'),
                        ]);
                    }
                }
            }
        }
    }
}