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

    public function test_complete_product_workflow()
    {
        Http::fake([
            '*/product/202502/products/search' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => [
                    'products' => [
                        [
                            'id' => 'product_123',
                            'title' => 'Test Product',
                            'status' => 'ACTIVATE'
                        ]
                    ],
                    'total_count' => 1,
                    'next_page_token' => 'next_token_123'
                ]
            ]),
            '*/product/202309/products/product_123' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => [
                    'id' => 'product_123',
                    'title' => 'Test Product',
                    'status' => 'ACTIVATE',
                    'description' => 'Product description',
                    'price' => '29.99',
                    'images' => []
                ]
            ])
        ]);

        // Step 1: Search for products
        $searchResult = $this->tiktok->product()->list(
            query: ['page_size' => 10],
            body: ['status' => 'ACTIVATE']
        );

        $this->assertEquals('0', $searchResult['code']);
        $this->assertArrayHasKey('products', $searchResult['data']);
        $this->assertCount(1, $searchResult['data']['products']);

        $product = $searchResult['data']['products'][0];

        // Step 2: Get detailed product information
        $productDetail = $this->tiktok->product()->get(
            params: ['product_id' => $product['id']]
        );

        $this->assertEquals('0', $productDetail['code']);
        $this->assertEquals('product_123', $productDetail['data']['id']);
        $this->assertEquals('Test Product', $productDetail['data']['title']);

        // Verify requests were logged
        $this->assertDatabaseHas('tiktok_requests', [
            'action' => 'ProductService::list',
            'code' => '0'
        ]);

        $this->assertDatabaseHas('tiktok_requests', [
            'action' => 'ProductService::get',
            'code' => '0'
        ]);
    }

    public function test_complete_order_workflow()
    {
        Http::fake([
            '*/order/202309/orders/search' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => [
                    'orders' => [
                        [
                            'id' => 'order_456',
                            'order_status' => 'UNPAID',
                            'create_time' => 1640995200
                        ]
                    ],
                    'total_count' => 1
                ]
            ]),
            '*/order/202309/orders*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => [
                    'orders' => [
                        [
                            'id' => 'order_456',
                            'order_status' => 'UNPAID',
                            'line_items' => [],
                            'payment_info' => [],
                            'shipping_info' => []
                        ]
                    ]
                ]
            ]),
            '*/order/202407/orders/order_456/price_detail' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => [
                    'order_id' => 'order_456',
                    'subtotal' => '100.00',
                    'tax' => '8.00',
                    'shipping' => '5.00',
                    'total' => '113.00'
                ]
            ])
        ]);

        // Step 1: Search for orders
        $ordersResult = $this->tiktok->order()->list(
            query: ['page_size' => 20],
            body: [
                'order_status' => 'UNPAID',
                'create_time_ge' => 1640995200
            ]
        );

        $this->assertEquals('0', $ordersResult['code']);
        $this->assertArrayHasKey('orders', $ordersResult['data']);

        $order = $ordersResult['data']['orders'][0];

        // Step 2: Get order details
        $orderDetail = $this->tiktok->order()->detail(
            query: ['ids' => $order['id']]
        );

        $this->assertEquals('0', $orderDetail['code']);
        $this->assertEquals('order_456', $orderDetail['data']['orders'][0]['id']);

        // Step 3: Get pricing details
        $pricingDetail = $this->tiktok->order()->priceDetail(
            params: ['order_id' => $order['id']]
        );

        $this->assertEquals('0', $pricingDetail['code']);
        $this->assertEquals('113.00', $pricingDetail['data']['total']);

        // Verify all requests were successful
        $this->assertDatabaseHas('tiktok_requests', ['action' => 'OrderService::list']);
        $this->assertDatabaseHas('tiktok_requests', ['action' => 'OrderService::detail']);
        $this->assertDatabaseHas('tiktok_requests', ['action' => 'OrderService::priceDetail']);
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

    public function test_error_handling_and_events()
    {
        Event::fake();

        Http::fake([
            '*' => Http::response([
                'code' => '1001',
                'message' => 'Invalid request parameters',
                'data' => null
            ])
        ]);

        $this->expectException(TikTokAPIError::class);

        try {
            $this->tiktok->product()->list(
                query: ['page_size' => 10],
                body: ['invalid_param' => 'invalid_value']
            );
        } catch (TikTokAPIError $e) {
            // Verify error details
            $this->assertEquals('1001', $e->getCode());
            $this->assertStringContainsString('Invalid request parameters', $e->getMessage());

            // Verify the request was logged with error
            $this->assertDatabaseHas('tiktok_requests', [
                'action' => 'ProductService::list',
                'code' => '1001',
                'message' => 'Invalid request parameters'
            ]);

            // Verify event was fired
            Event::assertDispatched(TikTokRequestFailed::class);

            throw $e;
        }
    }

    public function test_signature_generation_and_validation()
    {
        Http::fake();

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

    public function test_request_logging_captures_all_details()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => ['test' => 'response'],
                'request_id' => 'req_123456'
            ])
        ]);

        $this->tiktok->product()->list(
            query: ['page_size' => 15],
            body: ['status' => 'DRAFT', 'update_time_ge' => 1640995200]
        );

        $request = TiktokRequest::latest()->first();

        $this->assertNotNull($request);
        $this->assertEquals('test_shop_id', $request->shop_id);
        $this->assertEquals('ProductService::list', $request->action);
        $this->assertEquals('0', $request->code);
        $this->assertEquals('Success', $request->message);
        $this->assertEquals('req_123456', $request->request_id);
        $this->assertArrayHasKey('status', $request->request);
        $this->assertEquals('DRAFT', $request->request['status']);
        $this->assertArrayHasKey('test', $request->response);
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