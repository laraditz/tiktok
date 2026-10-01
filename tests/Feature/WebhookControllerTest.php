<?php

namespace Laraditz\TikTok\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Laraditz\TikTok\Tests\TestCase;
use Laraditz\TikTok\Events\WebhookReceived;
use Laraditz\TikTok\Exceptions\TikTokException;
use Laraditz\TikTok\Models\TiktokOrder;
use Laraditz\TikTok\Models\TiktokWebhook;
use Laraditz\TikTok\Models\TiktokReturnOrder;

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

    public function test_missing_type_passes_through_as_unknown_with_null_type_id()
    {
        $payload = $this->payload();
        unset($payload['type']);

        $this->postWebhook('all', $payload)->assertOk();

        $webhook = TiktokWebhook::first();

        $this->assertSame('UNKNOWN', $webhook->event_type);
        $this->assertNull($webhook->type_id);
    }

    public function test_non_numeric_type_passes_through_as_unknown_with_null_type_id()
    {
        $this->postWebhook('all', $this->payload(['type' => 'abc']))->assertOk();

        $webhook = TiktokWebhook::first();

        $this->assertSame('UNKNOWN', $webhook->event_type);
        $this->assertNull($webhook->type_id);
        $this->assertSame('abc', $webhook->event_data['type']);
    }

    public function test_numeric_string_type_resolves_to_enum_name()
    {
        $this->postWebhook('all', $this->payload(['type' => '1']))->assertOk();

        $webhook = TiktokWebhook::first();

        $this->assertSame('ORDER_STATUS_CHANGE', $webhook->event_type);
        $this->assertSame(1, (int) $webhook->type_id);
    }

    public function test_webhook_received_event_carries_type_id_for_known_type()
    {
        Event::fake([WebhookReceived::class]);

        $this->postWebhook('all', $this->payload())->assertOk();

        Event::assertDispatched(WebhookReceived::class, function (WebhookReceived $event) {
            return $event->eventType === 'ORDER_STATUS_CHANGE'
                && $event->typeId === 1
                && $event->data['type'] === 1;
        });
    }

    public function test_webhook_received_event_carries_type_id_for_unknown_type()
    {
        Event::fake([WebhookReceived::class]);

        $this->postWebhook('all', $this->payload(['type' => 9999]))->assertOk();

        Event::assertDispatched(WebhookReceived::class, function (WebhookReceived $event) {
            return $event->eventType === 'UNKNOWN' && $event->typeId === 9999;
        });
    }

    public function test_return_status_change_upserts_return_order()
    {
        $this->postWebhook('all', $this->payload([
            'type' => 12,
            'data' => [
                'order_id' => '576461413038785752',
                'return_id' => '4035312491762585666',
                'return_role' => 'BUYER',
                'return_type' => 'REFUND',
                'return_status' => 'RETURN_OR_REFUND_REQUEST_PENDING',
                'create_time' => 1776100000,
                'update_time' => 1776107000,
            ],
        ]))->assertOk();

        $returnOrder = TiktokReturnOrder::first();

        $this->assertNotNull($returnOrder);
        $this->assertSame('test_shop_id', $returnOrder->shop_id);
        $this->assertSame('576461413038785752', $returnOrder->order_id);
        $this->assertSame('4035312491762585666', $returnOrder->return_id);
        $this->assertSame('BUYER', $returnOrder->role);
        $this->assertSame('REFUND', $returnOrder->type);
        $this->assertSame('RETURN_OR_REFUND_REQUEST_PENDING', $returnOrder->status);
        $this->assertSame(1776100000, (int) $returnOrder->create_time);
        $this->assertSame(1776107000, (int) $returnOrder->update_time);
    }

    public function test_per_event_route_rejects_unknown_slug()
    {
        $this->withoutExceptionHandling();
        $this->expectException(TikTokException::class);
        $this->expectExceptionMessage('Invalid event type.');

        $this->postWebhook('not-a-real-event', $this->payload());
    }

    public function test_per_event_route_accepts_known_slug()
    {
        $this->postWebhook('order-status-change', $this->payload())->assertOk();

        $this->assertSame('ORDER_STATUS_CHANGE', TiktokWebhook::first()->event_type);
        $this->assertSame('AWAITING_SHIPMENT', TiktokOrder::find('576461413038785752')?->status);
    }
}
