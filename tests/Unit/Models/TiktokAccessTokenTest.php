<?php

namespace Laraditz\TikTok\Tests\Unit\Models;

use Laraditz\TikTok\Tests\TestCase;
use Laraditz\TikTok\Models\TiktokAccessToken;
use Laraditz\TikTok\Models\TiktokShop;
use Carbon\Carbon;

class TiktokAccessTokenTest extends TestCase
{
    public function test_can_create_access_token()
    {
        $shop = $this->createTikTokShop();

        $accessToken = TiktokAccessToken::create([
            'subjectable_id' => $shop->id,
            'subjectable_type' => TiktokShop::class,
            'access_token' => 'access_token_123',
            'refresh_token' => 'refresh_token_123',
            'expires_at' => now()->addDays(7),
            'refresh_expires_at' => now()->addDays(60),
            'open_id' => 'test_open_id',
            'seller_name' => 'Test Seller',
        ]);

        $this->assertInstanceOf(TiktokAccessToken::class, $accessToken);
        $this->assertEquals('access_token_123', $accessToken->access_token);
        $this->assertEquals('refresh_token_123', $accessToken->refresh_token);
        $this->assertEquals('test_open_id', $accessToken->open_id);
        $this->assertEquals('Test Seller', $accessToken->seller_name);
    }

    public function test_access_token_belongs_to_subjectable()
    {
        $shop = $this->createTikTokShop();
        $accessToken = $this->createAccessToken(['subjectable_id' => $shop->id]);

        $this->assertInstanceOf(TiktokShop::class, $accessToken->subjectable);
        $this->assertEquals($shop->id, $accessToken->subjectable->id);
    }

    public function test_access_token_fillable_attributes()
    {
        $accessToken = new TiktokAccessToken();
        $fillable = $accessToken->getFillable();

        $expectedFillable = [
            'subjectable_id',
            'subjectable_type',
            'access_token',
            'refresh_token',
            'expires_at',
            'refresh_expires_at',
            'open_id',
            'seller_name',
            'seller_base_region',
            'user_type',
            'granted_scopes',
            'code',
        ];

        foreach ($expectedFillable as $attribute) {
            $this->assertContains($attribute, $fillable);
        }
    }

    public function test_access_token_table_name()
    {
        $accessToken = new TiktokAccessToken();

        $this->assertEquals('tiktok_access_tokens', $accessToken->getTable());
    }

    public function test_access_token_dates_are_cast_to_carbon()
    {
        $accessToken = $this->createAccessToken();

        $this->assertInstanceOf(Carbon::class, $accessToken->expires_at);
        $this->assertInstanceOf(Carbon::class, $accessToken->refresh_expires_at);
        $this->assertInstanceOf(Carbon::class, $accessToken->created_at);
        $this->assertInstanceOf(Carbon::class, $accessToken->updated_at);
    }

    public function test_can_check_if_token_is_expired()
    {
        // Create expired token
        $expiredToken = $this->createAccessToken([
            'expires_at' => now()->subDays(1)
        ]);

        // Create valid token
        $validToken = $this->createAccessToken([
            'expires_at' => now()->addDays(1)
        ]);

        $this->assertTrue($expiredToken->expires_at < now());
        $this->assertFalse($validToken->expires_at < now());
    }

    public function test_can_check_if_refresh_token_is_expired()
    {
        // Create token with expired refresh token
        $expiredRefreshToken = $this->createAccessToken([
            'refresh_expires_at' => now()->subDays(1)
        ]);

        // Create token with valid refresh token
        $validRefreshToken = $this->createAccessToken([
            'refresh_expires_at' => now()->addDays(1)
        ]);

        $this->assertTrue($expiredRefreshToken->refresh_expires_at < now());
        $this->assertFalse($validRefreshToken->refresh_expires_at < now());
    }

    public function test_can_find_token_by_access_token()
    {
        $accessToken = $this->createAccessToken(['access_token' => 'findable_token']);

        $foundToken = TiktokAccessToken::where('access_token', 'findable_token')->first();

        $this->assertNotNull($foundToken);
        $this->assertEquals($accessToken->id, $foundToken->id);
    }

    public function test_can_create_multiple_tokens_for_same_shop()
    {
        $shop = $this->createTikTokShop();

        $token1 = $this->createAccessToken([
            'subjectable_id' => $shop->id,
            'access_token' => 'token_1'
        ]);

        $token2 = $this->createAccessToken([
            'subjectable_id' => $shop->id,
            'access_token' => 'token_2'
        ]);

        $this->assertNotEquals($token1->id, $token2->id);
        $this->assertEquals($shop->id, $token1->subjectable_id);
        $this->assertEquals($shop->id, $token2->subjectable_id);
    }

    public function test_can_get_tokens_expiring_soon()
    {
        // Create token expiring soon
        $expiringSoon = $this->createAccessToken([
            'expires_at' => now()->addHours(2)
        ]);

        // Create token not expiring soon
        $notExpiringSoon = $this->createAccessToken([
            'expires_at' => now()->addDays(5)
        ]);

        $expiringSoonTokens = TiktokAccessToken::where('expires_at', '<=', now()->addHours(24))->get();

        $this->assertTrue($expiringSoonTokens->contains($expiringSoon));
        $this->assertFalse($expiringSoonTokens->contains($notExpiringSoon));
    }

    public function test_can_scope_valid_tokens()
    {
        // Create expired token
        $expiredToken = $this->createAccessToken([
            'expires_at' => now()->subDays(1)
        ]);

        // Create valid token
        $validToken = $this->createAccessToken([
            'expires_at' => now()->addDays(1)
        ]);

        $validTokens = TiktokAccessToken::where('expires_at', '>', now())->get();

        $this->assertFalse($validTokens->contains($expiredToken));
        $this->assertTrue($validTokens->contains($validToken));
    }
}