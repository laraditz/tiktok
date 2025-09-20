<?php

namespace Laraditz\TikTok\Tests\Integration;

use Laraditz\TikTok\Tests\TestCase;
use Laraditz\TikTok\TikTok;
use Laraditz\TikTok\Models\TiktokRequest;
use Laraditz\TikTok\Events\TikTokRequestFailed;
use Laraditz\TikTok\Exceptions\TikTokAPIError;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;

class ApiIntegrationTest extends TestCase
{
    protected TikTok $tiktok;

    protected function setUp(): void
    {
        parent::setUp();

        $shop = $this->createTikTokShop();
        $this->createAccessToken(['subjectable_id' => $shop->id]);

        $this->tiktok = new TikTok();
        $this->tiktok->setShop($shop);
    }



    public function test_webhook_management_workflow()
    {
        Http::fake([
            '*/event/202309/webhooks*' => Http::sequence()
                ->push([
                    'code' => '0',
                    'message' => 'Success',
                    'data' => [
                        'webhooks' => []
                    ]
                ])
                ->push([
                    'code' => '0',
                    'message' => 'Success',
                    'data' => []
                ])
                ->push([
                    'code' => '0',
                    'message' => 'Success',
                    'data' => [
                        'webhooks' => [
                            [
                                'event_type' => 'ORDER_STATUS_CHANGE',
                                'address' => 'https://example.com/webhook/order'
                            ]
                        ]
                    ]
                ])
                ->push([
                    'code' => '0',
                    'message' => 'Success',
                    'data' => []
                ])
        ]);

        // Step 1: List existing webhooks
        $webhookList = $this->tiktok->event()->webhookList();
        $this->assertEquals('0', $webhookList['code']);

        // Step 2: Register a new webhook
        $updateResult = $this->tiktok->event()->updateWebhook(
            body: [
                'event_type' => 'ORDER_STATUS_CHANGE',
                'address' => 'https://example.com/webhook/order'
            ]
        );
        $this->assertEquals('0', $updateResult['code']);

        // Step 3: Verify webhook was registered
        $updatedList = $this->tiktok->event()->webhookList();
        $this->assertEquals('0', $updatedList['code']);
        $this->assertCount(1, $updatedList['data']['webhooks']);

        // Step 4: Delete the webhook
        $deleteResult = $this->tiktok->event()->deleteWebhook(
            body: ['event_type' => 'ORDER_STATUS_CHANGE']
        );
        $this->assertEquals('0', $deleteResult['code']);
    }


    public function test_signature_generation_and_validation()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => []
            ])
        ]);

        $this->tiktok->product()->list(
            query: ['page_size' => 10],
            body: ['status' => 'ACTIVATE']
        );

        Http::assertSent(function ($request) {
            $url = $request->url();

            // Verify signature is present
            $this->assertStringContainsString('sign=', $url);

            // Parse URL to get signature
            $urlParts = parse_url($url);
            parse_str($urlParts['query'], $queryParams);

            $this->assertArrayHasKey('sign', $queryParams);
            $this->assertEquals(64, strlen($queryParams['sign'])); // SHA256 length

            return true;
        });
    }


    public function test_concurrent_api_calls_different_services()
    {
        Http::fake([
            '*/product/*' => Http::response(['code' => '0', 'message' => 'Product Success', 'data' => []]),
            '*/order/*' => Http::response(['code' => '0', 'message' => 'Order Success', 'data' => []]),
            '*/seller/*' => Http::response(['code' => '0', 'message' => 'Seller Success', 'data' => []]),
        ]);

        // Simulate concurrent calls to different services
        $productResult = $this->tiktok->product()->list(
            query: ['page_size' => 10],
            body: ['status' => 'ACTIVATE']
        );

        $orderResult = $this->tiktok->order()->list(
            query: ['page_size' => 20],
            body: ['order_status' => 'UNPAID']
        );

        $sellerResult = $this->tiktok->seller()->shops();

        // All should succeed
        $this->assertEquals('0', $productResult['code']);
        $this->assertEquals('0', $orderResult['code']);
        $this->assertEquals('0', $sellerResult['code']);

        // Verify all requests were made
        Http::assertSentCount(3);

        // Verify all were logged separately
        $this->assertDatabaseHas('tiktok_requests', ['action' => 'ProductService::list']);
        $this->assertDatabaseHas('tiktok_requests', ['action' => 'OrderService::list']);
        $this->assertDatabaseHas('tiktok_requests', ['action' => 'SellerService::shops']);
    }
}