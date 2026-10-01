<?php

namespace Laraditz\TikTok\Tests\Feature;

use Laraditz\TikTok\Tests\TestCase;
use Laraditz\TikTok\Models\TiktokOrder;
use Laraditz\TikTok\Models\TiktokWebhook;

class WebhookControllerTest extends TestCase
{
    protected function postWebhook(string $event, array $payload)
    {
        $signature = app('tiktok')->getWebhookSignature(json_encode($payload));

        return $this->postJson("/tiktok/webhooks/{$event}", $payload, [
            'Authorization' => $signature,
        ]);
    }

    protected function payload(array $attributes = []): array
    {
        return array_merge([
            'type' => 1,
            'tts_notification_id' => '7628321701399676688',
            'shop_id' => 'test_shop_id',
            'timestamp' => 1776107052,
            'data' => [
                'order_id' => '576461413038785752',
                'order_status' => 'AWAITING_SHIPMENT',
                'is_on_hold_order' => false,
                'update_time' => 1776107000,
            ],
        ], $attributes);
    }

    public function test_known_type_resolves_to_enum_name_on_all_route()
    {
        $this->postWebhook('all', $this->payload())->assertOk();

        $webhook = TiktokWebhook::first();

        $this->assertSame('ORDER_STATUS_CHANGE', $webhook->event_type);
        $this->assertSame(1, (int) $webhook->type_id);
        $this->assertSame('AWAITING_SHIPMENT', TiktokOrder::find('576461413038785752')?->status);
    }

    public function test_unknown_type_passes_through_as_unknown_on_all_route()
    {
        $payload = $this->payload(['type' => 9999]);

        $this->postWebhook('all', $payload)->assertOk();

        $webhook = TiktokWebhook::first();

        $this->assertNotNull($webhook);
        $this->assertSame('UNKNOWN', $webhook->event_type);
        $this->assertSame(9999, (int) $webhook->type_id);
        $this->assertSame('test_shop_id', $webhook->shop_id);
        $this->assertEquals($payload, $webhook->event_data);
        $this->assertSame(0, TiktokOrder::count());
    }
}
