<?php

namespace Laraditz\TikTok\Tests\Unit;

use Laraditz\TikTok\Tests\TestCase;
use Laraditz\TikTok\TikTok;
use Laraditz\TikTok\Models\TiktokShop;
use Laraditz\TikTok\Models\TiktokAccessToken;
use Illuminate\Support\Facades\Http;

class SimpleTest extends TestCase
{
    public function test_can_create_tiktok_instance()
    {
        $tiktok = new TikTok();
        $this->assertInstanceOf(TikTok::class, $tiktok);
    }

    public function test_can_create_shop_model()
    {
        $shop = $this->createTikTokShop();
        $this->assertInstanceOf(TiktokShop::class, $shop);
        $this->assertEquals('test_shop_id', $shop->identifier);
    }

    public function test_can_create_access_token_model()
    {
        $token = $this->createAccessToken();
        $this->assertInstanceOf(TiktokAccessToken::class, $token);
        $this->assertEquals('test_access_token', $token->access_token);
    }

    public function test_can_access_auth_service()
    {
        $tiktok = new TikTok();
        $authService = $tiktok->auth();
        $this->assertInstanceOf(\Laraditz\TikTok\Services\AuthService::class, $authService);
    }

    public function test_http_mocking_works()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => [
                    'access_token' => 'test_token',
                    'refresh_token' => 'test_refresh',
                    'access_token_expire_in' => now()->addDays(7)->timestamp,
                    'refresh_token_expire_in' => now()->addDays(60)->timestamp,
                    'open_id' => 'test_open_id',
                    'seller_name' => 'Test Seller'
                ]
            ])
        ]);

        $tiktok = new TikTok();
        $result = $tiktok->auth()->accessToken(
            query: [
                'app_key' => 'test_app_key',
                'app_secret' => 'test_app_secret',
                'auth_code' => 'test_auth_code',
                'grant_type' => 'authorized_code'
            ]
        );

        $this->assertIsArray($result);
        $this->assertEquals('0', $result['code']);
        $this->assertEquals('test_token', $result['data']['access_token']);
    }

    public function test_can_make_api_call_with_shop()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => ['products' => []]
            ])
        ]);

        $shop = $this->createTikTokShop();
        $token = $this->createAccessToken(['subjectable_id' => $shop->id]);

        $tiktok = new TikTok();
        $tiktok->setShop($shop);
        $tiktok->setAccessToken($token->access_token);

        $result = $tiktok->product()->list(
            query: ['page_size' => 10],
            body: ['status' => 'ACTIVATE']
        );

        $this->assertIsArray($result);
        $this->assertEquals('0', $result['code']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'shop_cipher=test_cipher') &&
                   str_contains($request->url(), 'app_key=test_app_key');
        });
    }
}