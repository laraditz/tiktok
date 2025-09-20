<?php

namespace Laraditz\TikTok\Tests\Feature;

use Laraditz\TikTok\Tests\TestCase;
use Laraditz\TikTok\TikTok;
use Laraditz\TikTok\Models\TiktokShop;
use Laraditz\TikTok\Models\TiktokAccessToken;
use Laraditz\TikTok\Services\AuthService;
use Illuminate\Support\Facades\Http;

class AuthenticationFlowTest extends TestCase
{
    public function test_can_access_auth_service_without_shop()
    {
        $tiktok = new TikTok();
        $authService = $tiktok->auth();

        $this->assertInstanceOf(AuthService::class, $authService);
    }

    public function test_can_generate_access_token()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => [
                    'access_token' => 'new_access_token',
                    'refresh_token' => 'new_refresh_token',
                    'access_token_expire_in' => now()->addDays(7)->timestamp,
                    'refresh_token_expire_in' => now()->addDays(60)->timestamp,
                    'open_id' => 'test_open_id',
                    'seller_name' => 'Test Seller',
                    'seller_base_region' => 'MY',
                    'user_type' => 1,
                    'granted_scopes' => ['authorization']
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
        $this->assertEquals('new_access_token', $result['data']['access_token']);
        $this->assertEquals('new_refresh_token', $result['data']['refresh_token']);
    }


    public function test_auth_service_does_not_require_signature()
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
        $tiktok->auth()->accessToken(
            query: [
                'app_key' => 'test_app_key',
                'app_secret' => 'test_app_secret',
                'auth_code' => 'test_auth_code',
                'grant_type' => 'authorized_code'
            ]
        );

        Http::assertSent(function ($request) {
            $url = $request->url();
            // Auth service should not include sign parameter
            return !str_contains($url, 'sign=');
        });
    }

    public function test_auth_service_uses_auth_url()
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
        $tiktok->auth()->accessToken(
            query: [
                'app_key' => 'test_app_key',
                'app_secret' => 'test_app_secret',
                'auth_code' => 'test_auth_code',
                'grant_type' => 'authorized_code'
            ]
        );

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://auth.tiktok-shops.com');
        });
    }

    public function test_auth_service_does_not_include_access_token_header()
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
        $tiktok->auth()->accessToken(
            query: [
                'app_key' => 'test_app_key',
                'app_secret' => 'test_app_secret',
                'auth_code' => 'test_auth_code',
                'grant_type' => 'authorized_code'
            ]
        );

        Http::assertSent(function ($request) {
            $headers = $request->headers();
            // Auth service should not include access token header
            return !isset($headers['x-tts-access-token']);
        });
    }

}