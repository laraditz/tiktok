<?php

namespace Laraditz\TikTok\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laraditz\TikTok\TikTokServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            TikTokServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'TikTok' => \Laraditz\TikTok\Facades\TikTok::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('tiktok.app_key', 'test_app_key');
        $app['config']->set('tiktok.app_secret', 'test_app_secret');
        $app['config']->set('tiktok.shop_id', 'test_shop_id');
        $app['config']->set('tiktok.shop_code', 'test_shop_code');
        $app['config']->set('tiktok.shop_name', 'test_shop_name');
        $app['config']->set('tiktok.sign_method', 'sha256');
        $app['config']->set('tiktok.auth_url', 'https://auth.tiktok-shops.com');
        $app['config']->set('tiktok.base_url', 'https://open-api.tiktokglobalshop.com');

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function createTikTokShop(array $attributes = [])
    {
        return \Laraditz\TikTok\Models\TiktokShop::create(array_merge([
            'identifier' => 'test_shop_id',
            'code' => 'test_shop_code',
            'name' => 'Test Shop',
            'cipher' => 'test_cipher',
            'region' => 'MY',
            'seller_type' => 'test_seller',
        ], $attributes));
    }

    protected function createAccessToken(array $attributes = [])
    {
        $shop = isset($attributes['subjectable_id']) ? null : $this->createTikTokShop();

        return \Laraditz\TikTok\Models\TiktokAccessToken::create(array_merge([
            'subjectable_id' => $shop?->id ?? $attributes['subjectable_id'],
            'subjectable_type' => \Laraditz\TikTok\Models\TiktokShop::class,
            'access_token' => 'test_access_token',
            'refresh_token' => 'test_refresh_token',
            'expires_at' => now()->addDays(7),
            'refresh_expires_at' => now()->addDays(60),
            'open_id' => 'test_open_id',
            'seller_name' => 'Test Seller',
        ], $attributes));
    }
}