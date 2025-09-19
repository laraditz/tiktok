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
                    'expires_in' => 604800, // 7 days
                    'refresh_expires_in' => 5184000, // 60 days
                    'token_type' => 'bearer',
                    'scope' => 'authorization'
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

    public function test_can_refresh_access_token()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => [
                    'access_token' => 'refreshed_access_token',
                    'refresh_token' => 'refreshed_refresh_token',
                    'expires_in' => 604800,
                    'refresh_expires_in' => 5184000,
                    'token_type' => 'bearer',
                    'scope' => 'authorization'
                ]
            ])
        ]);

        $shop = $this->createTikTokShop();
        $accessToken = $this->createAccessToken(['subjectable_id' => $shop->id]);

        $tiktok = new TikTok();
        $result = $tiktok->auth()->refreshAccessToken($accessToken);

        $this->assertIsArray($result);
        $this->assertEquals('0', $result['code']);
        $this->assertEquals('refreshed_access_token', $result['data']['access_token']);
    }

    public function test_auth_service_does_not_require_signature()
    {
        Http::fake();

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
        Http::fake();

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
        Http::fake();

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

    public function test_complete_authentication_workflow()
    {
        // Step 1: Generate access token
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => [
                    'access_token' => 'workflow_access_token',
                    'refresh_token' => 'workflow_refresh_token',
                    'expires_in' => 604800,
                    'refresh_expires_in' => 5184000,
                    'token_type' => 'bearer',
                    'scope' => 'authorization'
                ]
            ])
        ]);

        $tiktok = new TikTok();
        $tokenResult = $tiktok->auth()->accessToken(
            query: [
                'app_key' => 'test_app_key',
                'app_secret' => 'test_app_secret',
                'auth_code' => 'test_auth_code',
                'grant_type' => 'authorized_code'
            ]
        );

        $this->assertEquals('0', $tokenResult['code']);

        // Step 2: Create shop and token manually (simulating callback processing)
        $shop = TiktokShop::create([
            'identifier' => 'workflow_shop_id',
            'code' => 'WORKFLOW123',
            'name' => 'Workflow Test Shop',
            'cipher' => 'workflow_cipher',
            'region' => 'US',
            'seller_type' => 'workflow_seller',
        ]);

        $accessToken = TiktokAccessToken::create([
            'subjectable_id' => $shop->id,
            'subjectable_type' => TiktokShop::class,
            'access_token' => $tokenResult['data']['access_token'],
            'refresh_token' => $tokenResult['data']['refresh_token'],
            'expires_at' => now()->addSeconds($tokenResult['data']['expires_in']),
            'refresh_expires_at' => now()->addSeconds($tokenResult['data']['refresh_expires_in']),
            'token_type' => $tokenResult['data']['token_type'],
            'scope' => $tokenResult['data']['scope'],
        ]);

        // Step 3: Use the token for API calls
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => ['shops' => []]
            ])
        ]);

        $tiktok->setShop($shop);
        $tiktok->setAccessToken($accessToken->access_token);
        $sellersResult = $tiktok->seller()->shops();

        $this->assertEquals('0', $sellersResult['code']);

        // Verify the request was made with correct headers and parameters
        Http::assertSent(function ($request) {
            $headers = $request->headers();
            $url = $request->url();

            return isset($headers['x-tts-access-token']) &&
                   in_array('workflow_access_token', $headers['x-tts-access-token']) &&
                   str_contains($url, 'shop_cipher=workflow_cipher');
        });
    }

    public function test_token_refresh_workflow()
    {
        // Create an expiring token
        $shop = $this->createTikTokShop();
        $expiringToken = $this->createAccessToken([
            'subjectable_id' => $shop->id,
            'access_token' => 'expiring_token',
            'refresh_token' => 'valid_refresh_token',
            'expires_at' => now()->addHours(1), // Expiring soon
        ]);

        // Mock the refresh token response
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => [
                    'access_token' => 'new_fresh_token',
                    'refresh_token' => 'new_refresh_token',
                    'expires_in' => 604800,
                    'refresh_expires_in' => 5184000,
                    'token_type' => 'bearer',
                    'scope' => 'authorization'
                ]
            ])
        ]);

        $tiktok = new TikTok();
        $refreshResult = $tiktok->auth()->refreshAccessToken($expiringToken);

        $this->assertEquals('0', $refreshResult['code']);
        $this->assertEquals('new_fresh_token', $refreshResult['data']['access_token']);
        $this->assertEquals('new_refresh_token', $refreshResult['data']['refresh_token']);

        // Verify refresh token request
        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://auth.tiktok-shops.com') &&
                   str_contains($request->url(), '/api/v2/token/refresh');
        });
    }
}