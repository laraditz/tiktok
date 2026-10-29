<?php

namespace Laraditz\TikTok\Tests\Unit;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laraditz\TikTok\Events\SellerAuthorized;
use Laraditz\TikTok\Models\TiktokAccessToken;
use Laraditz\TikTok\Models\TiktokRequest;
use Laraditz\TikTok\Models\TiktokShop;
use Laraditz\TikTok\Services\AuthorizationService;
use Laraditz\TikTok\Services\AuthService;
use Laraditz\TikTok\Tests\TestCase;
use Laraditz\TikTok\TikTok;

class OrtiroHooksTest extends TestCase
{
    public function test_authorization_url_with_state()
    {
        config()->set('tiktok.service_id', 'SVC123');

        $this->assertSame(
            'https://services.tiktokshop.com/open/authorize?service_id=SVC123&state=a+b%26c',
            (new TikTok())->authorizationUrl('a b&c')
        );
        $this->assertSame(
            'https://services.tiktokshop.com/open/authorize?service_id=SVC123',
            (new TikTok())->authorizationUrl()
        );
    }

    public function test_authorization_url_requires_service_id()
    {
        config()->set('tiktok.service_id', null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Missing Service ID.');

        (new TikTok())->authorizationUrl();
    }

    public function test_webhook_signature_valid()
    {
        $tiktok = new TikTok();
        $body = '{"type":1,"shop_id":"1"}';
        $signature = $tiktok->getWebhookSignature($body);

        $this->assertTrue($tiktok->verifyWebhookSignature($body, $signature));
        $this->assertTrue($tiktok->verifyWebhookSignature($body, '  ' . strtoupper($signature) . ' '));
    }

    public function test_webhook_signature_rejects_tampered_body()
    {
        $tiktok = new TikTok();
        $signature = $tiktok->getWebhookSignature('{"a":1}');

        $this->assertFalse($tiktok->verifyWebhookSignature('{"a":2}', $signature));
    }

    public function test_webhook_signature_rejects_empty_header()
    {
        $tiktok = new TikTok();

        $this->assertFalse($tiktok->verifyWebhookSignature('{"a":1}', ''));
        $this->assertFalse($tiktok->verifyWebhookSignature('{"a":1}', '   '));
    }

    public function test_access_token_dispatches_seller_authorized_with_all_shops()
    {
        Event::fake([SellerAuthorized::class]);

        Http::fake([
            'auth.tiktok-shops.com/*' => Http::response([
                'code' => 0,
                'message' => 'Success',
                'data' => [
                    'access_token' => 'new_access_token',
                    'refresh_token' => 'new_refresh_token',
                    'access_token_expire_in' => now()->addDays(7)->timestamp,
                    'refresh_token_expire_in' => now()->addDays(60)->timestamp,
                    'open_id' => 'OPEN_X',
                    'seller_name' => 'Seller X',
                    'seller_base_region' => 'MY',
                    'user_type' => 0,
                    'granted_scopes' => ['authorization'],
                ],
            ]),
            '*/authorization/*' => Http::response([
                'code' => 0,
                'message' => 'Success',
                'data' => ['shops' => [
                    ['id' => 'shop_1', 'code' => 'C1', 'name' => 'Shop 1', 'region' => 'MY', 'seller_type' => 'LOCAL', 'cipher' => 'cipher1'],
                    ['id' => 'shop_2', 'code' => 'C2', 'name' => 'Shop 2', 'region' => 'MY', 'seller_type' => 'LOCAL', 'cipher' => 'cipher2'],
                ]],
            ]),
        ]);

        (new TikTok())->auth()->accessToken(query: [
            'app_key' => 'test_app_key',
            'app_secret' => 'test_app_secret',
            'auth_code' => 'the_code',
            'grant_type' => 'authorized_code',
        ]);

        Event::assertDispatchedTimes(SellerAuthorized::class, 1);
        Event::assertDispatched(SellerAuthorized::class, function (SellerAuthorized $e) {
            return $e->openId === 'OPEN_X'
                && $e->shops instanceof EloquentCollection
                && $e->shops->pluck('id')->sort()->values()->all() === ['shop_1', 'shop_2']
                && $e->shops->every(fn($s) => $s->relationLoaded('accessToken') && $s->accessToken?->access_token === 'new_access_token');
        });
    }

    public function test_authorization_url_encodes_service_id()
    {
        config()->set('tiktok.service_id', 'a b&c');

        $this->assertSame(
            'https://services.tiktokshop.com/open/authorize?service_id=a+b%26c',
            (new TikTok())->authorizationUrl()
        );
    }

    public function test_webhook_signature_rejects_empty_app_secret()
    {
        config()->set('tiktok.app_secret', '');
        $tiktok = new TikTok(app_secret: '');
        $body = '{"type":1}';
        $signature = hash_hmac('sha256', 'test_app_key' . $body, '');

        $this->assertFalse($tiktok->verifyWebhookSignature($body, $signature));
    }

    private function shopsRequest(array $shops): void
    {
        (new AuthorizationService(new TikTok()))->afterShopsRequest(
            new TiktokRequest(),
            ['code' => 0, 'message' => 'Success', 'data' => ['shops' => $shops]]
        );
    }

    private function shopPayload(string $id, string $code, string $name): array
    {
        return ['id' => $id, 'code' => $code, 'name' => $name, 'region' => 'MY', 'seller_type' => 'LOCAL', 'cipher' => 'cipher_' . $id];
    }

    public function test_shops_request_updates_every_row_matching_id_or_code()
    {
        $this->createTikTokShop(['id' => 'legacy_1', 'code' => 'C1', 'name' => 'Old A']);
        $this->createTikTokShop(['id' => 'shop_1', 'code' => 'C1', 'name' => 'Old B']);

        $this->shopsRequest([$this->shopPayload('shop_1', 'C1', 'New Name')]);

        $this->assertSame(2, TiktokShop::count());
        $this->assertSame(['New Name', 'New Name'], TiktokShop::orderBy('id')->pluck('name')->all());
        $this->assertSame('cipher_shop_1', TiktokShop::find('legacy_1')->cipher);
    }

    public function test_shops_request_creates_when_none_match()
    {
        $this->shopsRequest([$this->shopPayload('shop_9', 'C9', 'Nine')]);

        $this->assertSame('Nine', TiktokShop::find('shop_9')->name);
    }

    public function test_shops_request_with_null_code_matches_by_id_only()
    {
        $this->createTikTokShop(['id' => 'a', 'code' => null, 'name' => 'A']);
        $this->createTikTokShop(['id' => 'b', 'code' => null, 'name' => 'B']);

        $this->shopsRequest([['id' => 'a', 'code' => null, 'name' => 'A2', 'region' => 'MY', 'seller_type' => 'LOCAL', 'cipher' => 'x']]);

        $this->assertSame('A2', TiktokShop::find('a')->name);
        $this->assertSame('B', TiktokShop::find('b')->name);
    }

    public function test_refresh_creating_token_row_still_syncs_siblings()
    {
        $shopA = $this->createTikTokShop(['id' => 'shop_a', 'code' => 'A', 'open_id' => 'OPEN1']);
        $shopB = $this->createTikTokShop(['id' => 'shop_b', 'code' => 'B', 'open_id' => 'OPEN1']);
        $sibling = $this->createAccessToken([
            'subjectable_id' => $shopB->id,
            'open_id' => 'OPEN1',
            'access_token' => 'old_access',
            'refresh_token' => 'old_refresh',
        ]);

        $request = new TiktokRequest(['shop_id' => 'shop_a']);
        (new AuthService(new TikTok()))->afterRefreshTokenRequest($request, [
            'data' => [
                'access_token' => 'new_access',
                'refresh_token' => 'new_refresh',
                'access_token_expire_in' => now()->addDays(7)->timestamp,
                'refresh_token_expire_in' => now()->addDays(60)->timestamp,
                'open_id' => 'OPEN1',
            ],
        ]);

        $this->assertSame('new_access', TiktokAccessToken::where('subjectable_id', 'shop_a')->first()->access_token);
        $this->assertSame('new_access', $sibling->fresh()->access_token);
        $this->assertSame('new_refresh', $sibling->fresh()->refresh_token);
    }
}
