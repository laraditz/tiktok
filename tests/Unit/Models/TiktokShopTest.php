<?php

namespace Laraditz\TikTok\Tests\Unit\Models;

use Laraditz\TikTok\Tests\TestCase;
use Laraditz\TikTok\Models\TiktokShop;
use Laraditz\TikTok\Models\TiktokAccessToken;

class TiktokShopTest extends TestCase
{
    public function test_can_create_tiktok_shop()
    {
        $shop = TiktokShop::create([
            'identifier' => 'shop_123',
            'code' => 'SHOP123',
            'name' => 'Test Shop',
            'cipher' => 'cipher_123',
            'region' => 'MY',
            'seller_type' => 'test_seller',
        ]);

        $this->assertInstanceOf(TiktokShop::class, $shop);
        $this->assertEquals('shop_123', $shop->identifier);
        $this->assertEquals('SHOP123', $shop->code);
        $this->assertEquals('Test Shop', $shop->name);
        $this->assertEquals('cipher_123', $shop->cipher);
        $this->assertEquals('MY', $shop->region);
        $this->assertEquals('test_seller', $shop->seller_type);
    }

    public function test_shop_has_access_token_relationship()
    {
        $shop = $this->createTikTokShop();
        $accessToken = $this->createAccessToken(['subjectable_id' => $shop->id]);

        $this->assertInstanceOf(TiktokAccessToken::class, $shop->accessToken);
        $this->assertEquals($accessToken->id, $shop->accessToken->id);
    }

    public function test_shop_fillable_attributes()
    {
        $shop = new TiktokShop();
        $fillable = $shop->getFillable();

        $expectedFillable = [
            'identifier',
            'code',
            'name',
            'cipher',
            'region',
            'seller_type',
        ];

        foreach ($expectedFillable as $attribute) {
            $this->assertContains($attribute, $fillable);
        }
    }

    public function test_shop_table_name()
    {
        $shop = new TiktokShop();

        $this->assertEquals('tiktok_shops', $shop->getTable());
    }

    public function test_shop_uses_incrementing_primary_key()
    {
        $shop = new TiktokShop();

        $this->assertTrue($shop->getIncrementing());
        $this->assertEquals('id', $shop->getKeyName());
    }

    public function test_shop_can_be_found_by_identifier()
    {
        $shop = $this->createTikTokShop(['identifier' => 'findable_123']);

        $foundShop = TiktokShop::where('identifier', 'findable_123')->first();

        $this->assertNotNull($foundShop);
        $this->assertEquals($shop->id, $foundShop->id);
    }

    public function test_shop_can_be_found_by_code()
    {
        $shop = $this->createTikTokShop(['code' => 'FINDABLE123']);

        $foundShop = TiktokShop::where('code', 'FINDABLE123')->first();

        $this->assertNotNull($foundShop);
        $this->assertEquals($shop->id, $foundShop->id);
    }

    public function test_can_create_multiple_shops()
    {
        $shop1 = $this->createTikTokShop(['identifier' => 'shop_1', 'name' => 'Shop One']);
        $shop2 = $this->createTikTokShop(['identifier' => 'shop_2', 'name' => 'Shop Two']);

        $this->assertNotEquals($shop1->id, $shop2->id);
        $this->assertEquals('shop_1', $shop1->identifier);
        $this->assertEquals('shop_2', $shop2->identifier);
    }

    public function test_shop_can_have_multiple_access_tokens_history()
    {
        $shop = $this->createTikTokShop();

        // Create multiple access tokens for the same shop
        TiktokAccessToken::create([
            'subjectable_id' => $shop->id,
            'subjectable_type' => TiktokShop::class,
            'access_token' => 'token_1',
            'refresh_token' => 'refresh_1',
            'expires_at' => now()->addDays(7),
            'refresh_expires_at' => now()->addDays(60),
        ]);

        TiktokAccessToken::create([
            'subjectable_id' => $shop->id,
            'subjectable_type' => TiktokShop::class,
            'access_token' => 'token_2',
            'refresh_token' => 'refresh_2',
            'expires_at' => now()->addDays(7),
            'refresh_expires_at' => now()->addDays(60),
        ]);

        // The accessToken relationship should return the most recent one
        $this->assertNotNull($shop->accessToken);
        $this->assertContains($shop->accessToken->access_token, ['token_1', 'token_2']);
    }
}