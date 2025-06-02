<?php

namespace Laraditz\TikTok\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laraditz\TikTok\Models\TiktokOrder;
use Laraditz\TikTok\Models\TiktokRequest;
use Laraditz\TikTok\Exceptions\TikTokTokenException;

class OrderService extends BaseService
{
    public function afterListRequest(TiktokRequest $request, array $result = []): void
    {
        $count = data_get($result, 'data.total_count');
        $orders = data_get($result, 'data.orders');
        $shopId = $this->tiktok->getShopId();

        if ($orders && is_array($orders) && $count > 0) {
            foreach ($orders as $order) {
                $this->addOrder($shopId, $order);
            }
        }
    }

    public function afterDetailRequest(TiktokRequest $request, array $result = []): void
    {
        $orders = data_get($result, 'data.orders');
        $shopId = $this->tiktok->getShopId();

        if ($orders && is_array($orders) && count($orders) > 0) {
            foreach ($orders as $order) {
                $this->addOrder($shopId, $order);
            }
        }
    }

    private function addOrder(string $shopId, array $order)
    {
        $orderId = data_get($order, 'id');
        $status = data_get($order, 'status');
        $isOnHoldOrder = data_get($order, 'is_on_hold_order');
        $updateTime = data_get($order, 'update_time');

        TiktokOrder::updateOrCreate([
            'id' => $orderId,
            'shop_id' => $shopId
        ], [
            'status' => $status,
            'is_on_hold_order' => $isOnHoldOrder,
            'update_time' => $updateTime,
        ]);

    }

}