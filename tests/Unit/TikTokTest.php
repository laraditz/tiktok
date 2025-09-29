<?php

namespace Laraditz\TikTok\Tests\Unit;

use Laraditz\TikTok\Tests\TestCase;
use Laraditz\TikTok\TikTok;
use Laraditz\TikTok\Models\TiktokShop;
use Laraditz\TikTok\Models\TiktokAccessToken;
use Laraditz\TikTok\Services\AuthService;
use Laraditz\TikTok\Services\ProductService;
use Laraditz\TikTok\Services\OrderService;
use LogicException;
use BadMethodCallException;

class TikTokTest extends TestCase
{
    public function test_can_instantiate_tiktok_class()
    {
        $tiktok = new TikTok();

        $this->assertInstanceOf(TikTok::class, $tiktok);
        $this->assertEquals('test_app_key', $tiktok->getAppKey());
        $this->assertEquals('test_app_secret', $tiktok->getAppSecret());
    }

    public function test_can_use_make_static_method()
    {
        $tiktok = TikTok::make();

        $this->assertInstanceOf(TikTok::class, $tiktok);
    }

    public function test_can_make_with_custom_parameters()
    {
        $tiktok = TikTok::make(
            app_key: 'custom_key',
            app_secret: 'custom_secret',
            shop_id: 'custom_shop_id'
        );

        $this->assertEquals('custom_key', $tiktok->getAppKey());
        $this->assertEquals('custom_secret', $tiktok->getAppSecret());
        $this->assertEquals('custom_shop_id', $tiktok->getShopId());
    }

    public function test_can_set_and_get_app_credentials()
    {
        $tiktok = new TikTok();

        $tiktok->setAppKey('new_key');
        $tiktok->setAppSecret('new_secret');

        $this->assertEquals('new_key', $tiktok->getAppKey());
        $this->assertEquals('new_secret', $tiktok->getAppSecret());
    }

    public function test_can_set_and_get_shop_details()
    {
        $tiktok = new TikTok();

        $tiktok->setShopId('shop_123');
        $tiktok->setShopCode('SHOP123');
        $tiktok->setShopName('Test Shop');

        $this->assertEquals('shop_123', $tiktok->getShopId());
        $this->assertEquals('SHOP123', $tiktok->getShopCode());
        $this->assertEquals('Test Shop', $tiktok->getShopName());
    }

    public function test_shop_id_fluent_method()
    {
        $tiktok = new TikTok();
        $result = $tiktok->shopId('fluent_shop_id');

        $this->assertSame($tiktok, $result);
        $this->assertEquals('fluent_shop_id', $tiktok->getShopId());
    }

    public function test_can_access_auth_service()
    {
        $tiktok = new TikTok();
        $authService = $tiktok->auth();

        $this->assertInstanceOf(AuthService::class, $authService);
    }

    public function test_can_access_product_service()
    {
        $shop = $this->createTikTokShop();
        $this->createAccessToken(['subjectable_id' => $shop->id]);

        $tiktok = new TikTok();
        $productService = $tiktok->product();

        $this->assertInstanceOf(ProductService::class, $productService);
    }

    public function test_can_access_order_service()
    {
        $shop = $this->createTikTokShop();
        $this->createAccessToken(['subjectable_id' => $shop->id]);

        $tiktok = new TikTok();
        $orderService = $tiktok->order();

        $this->assertInstanceOf(OrderService::class, $orderService);
    }

    public function test_throws_exception_for_missing_app_key()
    {
        $tiktok = new TikTok();
        $tiktok->setAppKey('');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Missing App Key.');

        $tiktok->product();
    }

    public function test_throws_exception_for_missing_app_secret()
    {
        $tiktok = new TikTok();
        $tiktok->setAppSecret('');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Missing App Secret.');

        $tiktok->product();
    }

    public function test_throws_exception_for_invalid_service()
    {
        $tiktok = new TikTok();

        $this->expectException(BadMethodCallException::class);

        $tiktok->invalidService();
    }

    public function test_can_generate_signature()
    {
        $tiktok = new TikTok();
        $tiktok->setAppSecret('test_secret');

        $signature = $tiktok->getSignature(
            '/test/route',
            'POST',
            ['param1' => 'value1'],
            ['body_param' => 'body_value']
        );

        $this->assertIsString($signature);
        $this->assertEquals(64, strlen($signature)); // SHA256 hash length
    }

    public function test_can_generate_webhook_signature()
    {
        $tiktok = new TikTok();
        $tiktok->setAppKey('test_key');
        $tiktok->setAppSecret('test_secret');

        $signature = $tiktok->getWebhookSignature('{"test": "body"}');

        $this->assertIsString($signature);
        $this->assertEquals(64, strlen($signature)); // SHA256 hash length
    }

    public function test_get_sign_method_returns_configured_value()
    {
        $tiktok = new TikTok();

        $this->assertEquals('sha256', $tiktok->getSignMethod());
    }

    public function test_check_shop_finds_shop_by_id()
    {
        $shop = $this->createTikTokShop(['id' => 'find_me']);
        $this->createAccessToken(['subjectable_id' => $shop->id]);

        $tiktok = new TikTok();
        $tiktok->setShopId('find_me');
        $tiktok->checkShop();

        $this->assertNotNull($tiktok->getShop());
        $this->assertEquals('find_me', $tiktok->getShop()->id);
    }

    public function test_check_shop_finds_shop_by_code()
    {
        $shop = $this->createTikTokShop(['code' => 'FINDME123']);

        $tiktok = new TikTok();
        $tiktok->setShopCode('FINDME123');
        $tiktok->checkShop();

        $this->assertNotNull($tiktok->getShop());
        $this->assertEquals('FINDME123', $tiktok->getShop()->code);
    }

    public function test_check_shop_finds_shop_by_name()
    {
        $shop = $this->createTikTokShop(['name' => 'Find Me Shop']);

        $tiktok = new TikTok();
        $tiktok->setShopName('Find Me Shop');
        $tiktok->checkShop();

        $this->assertNotNull($tiktok->getShop());
        $this->assertEquals('Find Me Shop', $tiktok->getShop()->name);
    }

    public function test_can_set_and_get_shop()
    {
        $shop = $this->createTikTokShop();
        $tiktok = new TikTok();

        $tiktok->setShop($shop);

        $this->assertSame($shop, $tiktok->getShop());
    }

    public function test_get_shop_cipher_returns_shop_cipher()
    {
        $shop = $this->createTikTokShop(['cipher' => 'test_cipher_123']);
        $tiktok = new TikTok();

        $tiktok->setShop($shop);

        $this->assertEquals('test_cipher_123', $tiktok->getShopCipher());
    }

    public function test_get_shop_cipher_returns_null_when_no_shop()
    {
        $tiktok = new TikTok();

        $this->assertNull($tiktok->getShopCipher());
    }

    public function test_magic_call_with_shop_id_parameter()
    {
        $shop = $this->createTikTokShop(['id' => 'magic_shop_id']);
        $this->createAccessToken(['subjectable_id' => $shop->id]);

        $tiktok = new TikTok();

        // This should work without throwing an exception
        $productService = $tiktok->product(shop_id: 'magic_shop_id');

        $this->assertInstanceOf(ProductService::class, $productService);
        $this->assertEquals('magic_shop_id', $tiktok->getShopId());
    }

    public function test_get_route_path_returns_configured_route()
    {
        $tiktok = new TikTok();

        // Test with existing route from config
        $route = $tiktok->getRoutePath('auth.access_token');

        $this->assertEquals('/api/v2/token/get', $route);
    }

    public function test_get_route_path_returns_null_for_nonexistent_route()
    {
        $tiktok = new TikTok();

        $route = $tiktok->getRoutePath('nonexistent.route');

        $this->assertNull($route);
    }
}