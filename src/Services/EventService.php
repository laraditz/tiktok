<?php

namespace Laraditz\TikTok\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laraditz\TikTok\Models\TiktokEventWebhook;
use Laraditz\TikTok\Models\TiktokRequest;
use Laraditz\TikTok\Exceptions\TikTokTokenException;

class EventService extends BaseService
{
    public function afterWebhookListRequest(TiktokRequest $request, array $result = []): void
    {
        // $code = data_get($result, 'code');
        // $message = data_get($result, 'message');
        // $webhooks = data_get($result, 'data.webhooks');

        // if (($code === 0 || $message === 'Success') && $webhooks && is_array($webhooks) && count($webhooks) > 0) {

        //     foreach ($webhooks as $webhook) {
        //         $event_type = data_get($webhook, 'event_type');
        //         $address = data_get($webhook, 'address');

        //         TiktokEventWebhook::updateOrCreate(
        //             [
        //                 'shop_id' => $this->tiktok?->getShop()?->id,
        //                 'event_type' => $event_type,
        //             ],
        //             [
        //                 'address' => $address,
        //             ]
        //         );
        //     }
        // }
    }

    public function afterUpdateWebhookRequest(TiktokRequest $request, array $result = []): void
    {
        $code = data_get($result, 'code');
        $message = data_get($result, 'message');
        $address = data_get($request, 'request.address');
        $event_type = data_get($request, 'request.event_type');

        if (($code === 0 || $message === 'Success') && $event_type) {
            TiktokEventWebhook::updateOrCreate(
                [
                    'shop_id' => $this->tiktok?->getShop()?->id,
                    'event_type' => $event_type,
                ],
                [
                    'address' => $address,
                ]
            );
        }
    }

    public function afterDeleteWebhookRequest(TiktokRequest $request, array $result = []): void
    {
        $code = data_get($result, 'code');
        $message = data_get($result, 'message');
        $event_type = data_get($request, 'request.event_type');

        if (($code === 0 || $message === 'Success') && $event_type) {
            TiktokEventWebhook::where('event_type', $event_type)->first()?->delete();
        }
    }
}