<?php

namespace Laraditz\TikTok\Http\Controllers;

use TikTok;
use Illuminate\Http\Request;
use Laraditz\TikTok\Enums\EventType;
use Laraditz\TikTok\Models\TiktokOrder;
use Laraditz\TikTok\Models\TiktokWebhook;
use Laraditz\TikTok\Events\WebhookReceived;
use Laraditz\TikTok\Models\TiktokReturnOrder;
use Laraditz\TikTok\Exceptions\TikTokException;

class WebhookController extends Controller
{
    public function event(string $event, Request $request)
    {
        $signature = $request->header('Authorization');

        throw_if(!$signature, TikTokException::class, __('Missing Signature.'));
        throw_if(!$request->all(), TikTokException::class, __('Missing payload.'));

        // logger()->info('TikTok web push: ', $request->all());

        $match_signature = app('tiktok')->getWebhookSignature(json_encode($request->all()));
        // dd($match_signature);

        throw_if($signature !== $match_signature, TikTokException::class, __('Signature not matched.'));

        $shopId = $request->string('shop_id');
        $typeId = $request->type;
        $eventType = null;
        $methodName = null;

        if ($event === 'all') {
            $eventType = EventType::tryFrom($typeId)?->name;

            if ($eventType) {
                $methodName = str($eventType)->lower()->camel()->value;
            }
        } else {
            $methodName = str($event)->camel()->value;
            $eventType = str($event)->replace('-', '_')->upper()->value;
        }

        if (!EventType::fromCase($eventType)) {
            throw new TikTokException(__('Invalid event type.'));
        }

        // dd($eventType, $methodName);

        $data = $request->data;

        if (!$data) {
            throw new TikTokException(__('Missing data.'));
        }

        try {
            event(new WebhookReceived(eventType: $eventType, data: $request->all()));

            $webhook = TiktokWebhook::create([
                'shop_id' => $shopId,
                'type_id' => $typeId,
                'event_type' => $eventType,
                'event_data' => $request->all(),
            ]);

            if (method_exists($this, $methodName)) {
                return $this->$methodName($webhook, $request);
            }
        } catch (\Throwable $th) {
            logger()->error('TikTok web push : ' . $th->getMessage(), $data);

            throw new TikTokException($th->getMessage());
        }


    }

    private function orderStatusChange(TiktokWebhook $webhook, Request $request)
    {
        $data = $request->data;

        if ($data) {
            $orderId = data_get($data, 'order_id');
            $shopId = $request->shop_id;

            TiktokOrder::updateOrCreate([
                'id' => $orderId,
                'shop_id' => $shopId
            ], [
                'status' => data_get($data, 'order_status'),
                'is_on_hold_order' => data_get($data, 'is_on_hold_order'),
                'update_time' => data_get($data, 'update_time'),
            ]);
        }

    }

    private function orderReturnStatusChange(TiktokWebhook $webhook, Request $request)
    {
        $data = $request->data;

        if ($data) {
            $orderId = data_get($data, 'order_id');
            $returnId = data_get($data, 'return_id');
            $shopId = $request->shop_id;

            TiktokReturnOrder::updateOrCreate([
                'order_id' => $orderId,
                'shop_id' => $shopId,
                'return_id' => $returnId,
            ], [
                'role' => data_get($data, 'return_role'),
                'type' => data_get($data, 'return_type'),
                'status' => data_get($data, 'return_status'),
                'create_time' => data_get($data, 'create_time'),
                'update_time' => data_get($data, 'update_time'),
            ]);
        }
    }
}
