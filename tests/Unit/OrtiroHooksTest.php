<?php

namespace Laraditz\TikTok\Tests\Unit;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laraditz\TikTok\Events\SellerAuthorized;
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
                && $e->shops->pluck('id')->sort()->values()->all() === ['shop_1', 'shop_2']
                && $e->shops->every(fn($s) => $s->relationLoaded('accessToken') && $s->accessToken?->access_token === 'new_access_token');
        });
    }
}
