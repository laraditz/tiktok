<?php

namespace Laraditz\TikTok\Tests\Unit\Services;

use Laraditz\TikTok\Tests\TestCase;
use Laraditz\TikTok\TikTok;
use Laraditz\TikTok\Services\ProductService;
use Illuminate\Support\Facades\Http;

class ProductServiceTest extends TestCase
{
    protected TikTok $tiktok;
    protected ProductService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tiktok = new TikTok();
        $shop = $this->createTikTokShop();
        $this->createAccessToken(['subjectable_id' => $shop->id]);
        $this->tiktok->setShop($shop);

        $this->service = new ProductService($this->tiktok);
    }

    public function test_product_service_extends_base_service()
    {
        $this->assertInstanceOf(\Laraditz\TikTok\Services\BaseService::class, $this->service);
    }

    public function test_can_call_product_list_method()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => [
                    'products' => [
                        [
                            'id' => 'product_1',
                            'title' => 'Test Product 1',
                            'status' => 'ACTIVATE'
                        ],
                        [
                            'id' => 'product_2',
                            'title' => 'Test Product 2',
                            'status' => 'ACTIVATE'
                        ]
                    ],
                    'total_count' => 2
                ]
            ])
        ]);

        $result = $this->service->list(
            query: ['page_size' => 10],
            body: ['status' => 'ACTIVATE']
        );

        $this->assertIsArray($result);
        $this->assertEquals('0', $result['code']);
        $this->assertEquals('Success', $result['message']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('products', $result['data']);
        $this->assertCount(2, $result['data']['products']);
    }

    public function test_can_call_product_get_method()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => [
                    'id' => 'product_123',
                    'title' => 'Test Product',
                    'status' => 'ACTIVATE',
                    'description' => 'Test product description',
                    'price' => '29.99'
                ]
            ])
        ]);

        $result = $this->service->get(
            params: ['product_id' => 'product_123']
        );

        $this->assertIsArray($result);
        $this->assertEquals('0', $result['code']);
        $this->assertEquals('Success', $result['message']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('product_123', $result['data']['id']);
        $this->assertEquals('Test Product', $result['data']['title']);
    }

    public function test_product_list_sends_correct_request()
    {
        Http::fake();

        $this->service->list(
            query: ['page_size' => 20],
            body: [
                'status' => 'ALL',
                'update_time_ge' => 1640995200
            ]
        );

        Http::assertSent(function ($request) {
            $url = $request->url();
            $body = $request->data();

            // Check if it's a POST request to the correct endpoint
            return $request->method() === 'POST' &&
                   str_contains($url, '/product/202502/products/search') &&
                   str_contains($url, 'page_size=20') &&
                   str_contains($url, 'app_key=test_app_key') &&
                   isset($body['status']) &&
                   $body['status'] === 'ALL' &&
                   isset($body['update_time_ge']) &&
                   $body['update_time_ge'] === 1640995200;
        });
    }

    public function test_product_get_sends_correct_request()
    {
        Http::fake();

        $this->service->get(
            params: ['product_id' => 'product_456']
        );

        Http::assertSent(function ($request) {
            $url = $request->url();

            // Check if it's a GET request to the correct endpoint with product_id
            return $request->method() === 'GET' &&
                   str_contains($url, '/product/202309/products/product_456') &&
                   str_contains($url, 'app_key=test_app_key');
        });
    }

    public function test_product_service_includes_shop_cipher()
    {
        Http::fake();

        $this->service->list(
            query: ['page_size' => 10],
            body: ['status' => 'ACTIVATE']
        );

        Http::assertSent(function ($request) {
            $url = $request->url();

            return str_contains($url, 'shop_cipher=test_cipher');
        });
    }

    public function test_product_service_includes_access_token_header()
    {
        Http::fake();

        $this->service->list(
            query: ['page_size' => 10],
            body: ['status' => 'ACTIVATE']
        );

        Http::assertSent(function ($request) {
            $headers = $request->headers();

            return isset($headers['x-tts-access-token']) &&
                   in_array('test_access_token', $headers['x-tts-access-token']);
        });
    }

    public function test_product_service_includes_signature()
    {
        Http::fake();

        $this->service->list(
            query: ['page_size' => 10],
            body: ['status' => 'ACTIVATE']
        );

        Http::assertSent(function ($request) {
            $url = $request->url();

            return str_contains($url, 'sign=');
        });
    }

    public function test_product_service_logs_request()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => []
            ])
        ]);

        $this->service->list(
            query: ['page_size' => 10],
            body: ['status' => 'ACTIVATE']
        );

        $this->assertDatabaseHas('tiktok_requests', [
            'shop_id' => 'test_shop_id',
            'action' => 'ProductService::list',
            'code' => '0'
        ]);
    }

    public function test_product_service_with_different_shop()
    {
        Http::fake();

        $otherShop = $this->createTikTokShop(['identifier' => 'other_shop_id']);
        $this->createAccessToken([
            'subjectable_id' => $otherShop->id,
            'access_token' => 'other_access_token'
        ]);

        // Call with specific shop_id
        $this->service->list(
            shop_id: 'other_shop_id',
            query: ['page_size' => 10],
            body: ['status' => 'ACTIVATE']
        );

        // Should now be using the other shop
        $this->assertEquals('other_shop_id', $this->tiktok->getShopId());
    }
}