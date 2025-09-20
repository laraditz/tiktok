<?php

namespace Laraditz\TikTok\Tests\Feature;

use Laraditz\TikTok\Tests\TestCase;
use Laraditz\TikTok\TikTok;
use Laraditz\TikTok\Models\TiktokShop;
use Laraditz\TikTok\Models\TiktokAccessToken;
use Illuminate\Support\Facades\Http;

class MultiShopSupportTest extends TestCase
{
    protected TiktokShop $shop1;
    protected TiktokShop $shop2;
    protected TiktokAccessToken $token1;
    protected TiktokAccessToken $token2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create first shop
        $this->shop1 = TiktokShop::create([
            'identifier' => 'shop_1_id',
            'code' => 'SHOP001',
            'name' => 'Shop One',
            'cipher' => 'shop_1_cipher',
            'region' => 'MY',
            'seller_type' => 'shop1_seller',
        ]);

        $this->token1 = TiktokAccessToken::create([
            'subjectable_id' => $this->shop1->id,
            'subjectable_type' => TiktokShop::class,
            'access_token' => 'shop_1_access_token',
            'refresh_token' => 'shop_1_refresh_token',
            'expires_at' => now()->addDays(7),
            'refresh_expires_at' => now()->addDays(60),
        ]);

        // Create second shop
        $this->shop2 = TiktokShop::create([
            'identifier' => 'shop_2_id',
            'code' => 'SHOP002',
            'name' => 'Shop Two',
            'cipher' => 'shop_2_cipher',
            'region' => 'UK',
            'seller_type' => 'shop2_seller',
        ]);

        $this->token2 = TiktokAccessToken::create([
            'subjectable_id' => $this->shop2->id,
            'subjectable_type' => TiktokShop::class,
            'access_token' => 'shop_2_access_token',
            'refresh_token' => 'shop_2_refresh_token',
            'expires_at' => now()->addDays(7),
            'refresh_expires_at' => now()->addDays(60),
        ]);
    }

    public function test_can_switch_between_shops_using_make()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => ['products' => []]
            ])
        ]);

        // Use first shop
        $tiktok1 = TikTok::make(shop_id: 'shop_1_id');
        $tiktok1->product()->list(
            query: ['page_size' => 10],
            body: ['status' => 'ACTIVATE']
        );

        // Use second shop
        $tiktok2 = TikTok::make(shop_id: 'shop_2_id');
        $tiktok2->product()->list(
            query: ['page_size' => 10],
            body: ['status' => 'ACTIVATE']
        );

        // Verify both requests were made with correct shop ciphers
        Http::assertSentCount(2);

        $requests = Http::recorded();

        $this->assertStringContainsString('shop_cipher=shop_1_cipher', $requests[0][0]->url());
        $this->assertStringContainsString('shop_cipher=shop_2_cipher', $requests[1][0]->url());

        // Verify access tokens were set correctly
        $this->assertArrayHasKey('x-tts-access-token', $requests[0][0]->headers());
        $this->assertArrayHasKey('x-tts-access-token', $requests[1][0]->headers());
        $this->assertContains('shop_1_access_token', $requests[0][0]->headers()['x-tts-access-token']);
        $this->assertContains('shop_2_access_token', $requests[1][0]->headers()['x-tts-access-token']);
    }

    public function test_can_switch_shops_using_shop_id_fluent_method()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => ['orders' => []]
            ])
        ]);

        $tiktok = new TikTok();

        // Use first shop
        $tiktok->shopId('shop_1_id')->order()->list(
            query: ['page_size' => 20],
            body: ['order_status' => 'UNPAID']
        );

        // Use second shop
        $tiktok->shopId('shop_2_id')->order()->list(
            query: ['page_size' => 20],
            body: ['order_status' => 'UNPAID']
        );

        Http::assertSentCount(2);

        $requests = Http::recorded();

        $this->assertStringContainsString('shop_cipher=shop_1_cipher', $requests[0][0]->url());
        $this->assertStringContainsString('shop_cipher=shop_2_cipher', $requests[1][0]->url());
    }

    public function test_can_specify_shop_id_in_method_call()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => ['returns' => []]
            ])
        ]);

        $tiktok = TikTok::make(shop_id: 'shop_2_id');

        // Call with the pre-set shop_id
        $tiktok->return()->list(
            query: ['page_size' => 15],
            body: ['create_time_ge' => 1640995200]
        );

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'shop_cipher=shop_2_cipher') &&
                in_array('shop_2_access_token', $request->headers()['x-tts-access-token'] ?? []);
        });
    }



    public function test_shop_auto_resolution_by_access_token()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => []
            ])
        ]);

        $tiktok = new TikTok();
        $tiktok->setAccessToken('shop_2_access_token');
        $tiktok->checkShop(); // Manually trigger shop resolution

        // Call with the resolved shop context
        $tiktok->product()->list(
            query: ['page_size' => 10],
            body: ['status' => 'ACTIVATE']
        );

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'shop_cipher=shop_2_cipher') &&
                in_array('shop_2_access_token', $request->headers()['x-tts-access-token'] ?? []);
        });
    }

    public function test_database_requests_logged_with_correct_shop_id()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => []
            ])
        ]);

        $tiktok1 = TikTok::make(shop_id: 'shop_1_id');
        $tiktok1->product()->list(query: ['page_size' => 10], body: ['status' => 'ACTIVATE']);

        $tiktok2 = TikTok::make(shop_id: 'shop_2_id');
        $tiktok2->order()->list(query: ['page_size' => 10], body: ['order_status' => 'UNPAID']);

        $this->assertDatabaseHas('tiktok_requests', [
            'shop_id' => 'shop_1_id',
            'action' => 'ProductService::list'
        ]);

        $this->assertDatabaseHas('tiktok_requests', [
            'shop_id' => 'shop_2_id',
            'action' => 'OrderService::list'
        ]);
    }

    public function test_different_shops_can_have_same_product_operations()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => ['products' => []]
            ])
        ]);

        // Both shops get their products
        TikTok::make(shop_id: 'shop_1_id')->product()->list(
            query: ['page_size' => 10],
            body: ['status' => 'ACTIVATE']
        );

        TikTok::make(shop_id: 'shop_2_id')->product()->list(
            query: ['page_size' => 20],
            body: ['status' => 'DRAFT']
        );

        Http::assertSentCount(2);

        $requests = Http::recorded();

        // Verify different page sizes and statuses were sent
        $this->assertStringContainsString('page_size=10', $requests[0][0]->url());
        $this->assertStringContainsString('page_size=20', $requests[1][0]->url());

        $request1Body = $requests[0][0]->data();
        $request2Body = $requests[1][0]->data();

        $this->assertEquals('ACTIVATE', $request1Body['status']);
        $this->assertEquals('DRAFT', $request2Body['status']);
    }

    public function test_shop_fallback_to_config_when_not_specified()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => []
            ])
        ]);

        // Update config to use shop_1_id as default
        config(['tiktok.shop_id' => 'shop_1_id']);

        $tiktok = new TikTok();
        $tiktok->product()->list(query: ['page_size' => 10], body: ['status' => 'ACTIVATE']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'shop_cipher=shop_1_cipher');
        });
    }
}