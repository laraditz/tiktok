<?php

namespace Laraditz\TikTok\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Laraditz\TikTok\Models\TiktokAccessToken;
use Laraditz\TikTok\Models\TiktokShop;
use Laraditz\TikTok\Services\AuthService;
use Laraditz\TikTok\Tests\TestCase;
use Laraditz\TikTok\TikTok;

class TokenRefreshSiblingsTest extends TestCase
{
    private function fakeRefresh(string $openId = 'OPEN1'): int
    {
        $expiresIn = now()->addDays(7)->timestamp;

        Http::fake([
            '*' => Http::response([
                'code' => 0,
                'message' => 'Success',
                'data' => [
                    'access_token' => 'new_access',
                    'refresh_token' => 'new_refresh',
                    'access_token_expire_in' => $expiresIn,
                    'refresh_token_expire_in' => now()->addDays(60)->timestamp,
                    'open_id' => $openId,
                ],
            ]),
        ]);

        return $expiresIn;
    }

    private function tokenFor(string $shopId, string $openId): TiktokAccessToken
    {
        $shop = $this->createTikTokShop(['id' => $shopId, 'code' => $shopId . '_code', 'open_id' => $openId]);

        return $this->createAccessToken([
            'subjectable_id' => $shop->id,
            'open_id' => $openId,
            'access_token' => 'old_access_' . $shopId,
            'refresh_token' => 'old_refresh_' . $shopId,
        ]);
    }

    public function test_refresh_updates_sibling_rows_with_same_open_id()
    {
        $a = $this->tokenFor('shop_a', 'OPEN1');
        $b = $this->tokenFor('shop_b', 'OPEN1');
        $c = $this->tokenFor('shop_c', 'OPEN2');
        $this->fakeRefresh();

        (new TikTok())->auth()->refreshAccessToken($a);

        foreach ([$a->fresh(), $b->fresh()] as $row) {
            $this->assertSame('new_access', $row->access_token);
            $this->assertSame('new_refresh', $row->refresh_token);
            $this->assertTrue($row->expires_at->isAfter(now()->addDays(6)));
            $this->assertTrue($row->refresh_expires_at->isAfter(now()->addDays(59)));
        }

        $c = $c->fresh();
        $this->assertSame('old_access_shop_c', $c->access_token);
        $this->assertSame('old_refresh_shop_c', $c->refresh_token);
    }

    public function test_sibling_update_respects_encryption()
    {
        config()->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        config()->set('app.cipher', 'AES-256-CBC');
        config()->set('tiktok.encrypt_tokens', true);

        $a = $this->tokenFor('shop_a', 'OPEN1');
        $b = $this->tokenFor('shop_b', 'OPEN1');
        $this->fakeRefresh();

        (new TikTok())->auth()->refreshAccessToken($a);

        $raw = \Illuminate\Support\Facades\DB::table('tiktok_access_tokens')->where('id', $b->id)->first();
        $this->assertNotSame('new_refresh', $raw->refresh_token);
        $this->assertNotSame('new_access', $raw->access_token);
        $this->assertSame('new_refresh', $b->fresh()->refresh_token);
        $this->assertSame('new_access', $b->fresh()->access_token);
    }

    public function test_shops_request_creates_missing_shop()
    {
        Http::fake([
            '*' => Http::response([
                'code' => 0,
                'message' => 'Success',
                'data' => ['shops' => [[
                    'id' => 'new_shop', 'code' => 'NEWCODE', 'name' => 'New Shop',
                    'region' => 'MY', 'seller_type' => 'LOCAL', 'cipher' => 'c1',
                ]]],
            ]),
        ]);

        (new TikTok())->authorization(access_token: 'tok')->shops();

        $shop = TiktokShop::find('new_shop');
        $this->assertNotNull($shop);
        $this->assertSame('New Shop', $shop->name);
        $this->assertSame('NEWCODE', $shop->code);
    }

    public function test_shops_request_updates_existing_shop()
    {
        $this->createTikTokShop(['id' => 'shop_x', 'code' => 'CODEX', 'name' => 'Old Name']);

        Http::fake([
            '*' => Http::response([
                'code' => 0,
                'message' => 'Success',
                'data' => ['shops' => [[
                    'id' => 'shop_x', 'code' => 'CODEX', 'name' => 'Renamed',
                    'region' => 'SG', 'seller_type' => 'CROSS_BORDER', 'cipher' => 'c2',
                ]]],
            ]),
        ]);

        (new TikTok())->authorization(access_token: 'tok')->shops();

        $this->assertSame(1, TiktokShop::where('id', 'shop_x')->count());
        $shop = TiktokShop::find('shop_x');
        $this->assertSame('Renamed', $shop->name);
        $this->assertSame('SG', $shop->region);
    }

    public function test_access_token_stores_auth_code()
    {
        Http::fake([
            '*/api/v2/token/get*' => Http::response([
                'code' => 0,
                'message' => 'Success',
                'data' => [
                    'access_token' => 'at',
                    'refresh_token' => 'rt',
                    'access_token_expire_in' => now()->addDays(7)->timestamp,
                    'refresh_token_expire_in' => now()->addDays(60)->timestamp,
                    'open_id' => 'OPENX',
                    'seller_name' => 'S',
                ],
            ]),
            '*' => Http::response([
                'code' => 0,
                'message' => 'Success',
                'data' => ['shops' => [[
                    'id' => 'shop_ac', 'code' => 'AC', 'name' => 'AC Shop',
                    'region' => 'MY', 'seller_type' => 'LOCAL', 'cipher' => 'c3',
                ]]],
            ]),
        ]);

        (new TikTok())->auth()->accessToken(query: [
            'app_key' => 'test_app_key',
            'app_secret' => 'test_app_secret',
            'auth_code' => 'REAL_AUTH_CODE',
            'grant_type' => 'authorized_code',
        ]);

        $token = TiktokAccessToken::where('open_id', 'OPENX')->first();
        $this->assertNotNull($token);
        $this->assertSame('REAL_AUTH_CODE', $token->code);
    }

    public function test_default_timezone_key_is_read()
    {
        config()->set('app.timezone', null);
        config()->set('tiktok.default_timezone', 'Asia/Kuala_Lumpur');

        $method = new \ReflectionMethod(AuthService::class, 'getRefreshTokenData');
        $method->setAccessible(true);

        $data = $method->invoke(new AuthService(new TikTok()), [
            'access_token_expire_in' => now()->addDay()->timestamp,
        ]);

        $this->assertSame('Asia/Kuala_Lumpur', $data['expires_at']->timezoneName);
    }
}
