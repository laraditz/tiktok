<?php

namespace Laraditz\TikTok\Tests\Unit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laraditz\TikTok\Models\TiktokRequest;
use Laraditz\TikTok\Models\TiktokWebhook;
use Laraditz\TikTok\Tests\TestCase;
use Laraditz\TikTok\TikTok;
use PHPUnit\Framework\Attributes\Test;

class RequestLoggingTest extends TestCase
{
    private function exchange(): array
    {
        Http::fake([
            'auth.tiktok-shops.com/*' => Http::response([
                'code' => 0,
                'message' => 'Success',
                'request_id' => 'req-1',
                'data' => [
                    'access_token' => 'real-at',
                    'refresh_token' => 'real-rt',
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
                ]],
            ]),
        ]);

        return (new TikTok())->auth()->accessToken(query: [
            'app_key' => 'test_app_key',
            'app_secret' => 'real-secret',
            'auth_code' => 'real-code',
            'grant_type' => 'authorized_code',
        ]);
    }

    private function row(): object
    {
        return DB::table('tiktok_requests')->where('action', 'AuthService::accessToken')->first();
    }

    #[Test]
    public function test_default_logs_unredacted_request()
    {
        $this->exchange();

        $this->assertSame(1, DB::table('tiktok_requests')->where('action', 'AuthService::accessToken')->count());
        $this->assertStringContainsString('auth_code=real-code', $this->row()->url);
        $this->assertStringContainsString('app_secret=real-secret', $this->row()->url);
        $this->assertSame('real-at', json_decode($this->row()->response, true)['data']['access_token']);
        $this->assertStringNotContainsString('REDACTED', $this->row()->url . $this->row()->response);
    }

    #[Test]
    public function test_missing_logging_config_behaves_as_default()
    {
        config()->set('tiktok.logging', null);
        $this->exchange();

        $this->assertSame(1, DB::table('tiktok_requests')->where('action', 'AuthService::accessToken')->count());
        $this->assertStringContainsString('auth_code=real-code', $this->row()->url);
        $this->assertSame('real-at', json_decode($this->row()->response, true)['data']['access_token']);
    }

    #[Test]
    public function test_logging_disabled_writes_no_row_but_access_token_is_stored()
    {
        config()->set('tiktok.logging.requests', false);
        $this->exchange();

        $this->assertSame(0, DB::table('tiktok_requests')->count());
        $this->assertSame(1, DB::table('tiktok_access_tokens')->count());
        $this->assertSame('real-at', DB::table('tiktok_access_tokens')->value('access_token'));
    }

    #[Test]
    public function test_redaction_masks_request_response_and_url()
    {
        config()->set('tiktok.logging.redact_secrets', true);
        $result = $this->exchange();

        $this->assertSame('real-at', $result['data']['access_token']);

        $row = $this->row();
        $response = json_decode($row->response, true);
        parse_str(parse_url($row->url, PHP_URL_QUERY), $query);

        $this->assertSame('[REDACTED]', $query['app_secret']);
        $this->assertSame('[REDACTED]', $query['auth_code']);
        $this->assertSame('test_app_key', $query['app_key']);
        $this->assertSame('[REDACTED]', $response['data']['access_token']);
        $this->assertSame('[REDACTED]', $response['data']['refresh_token']);
        $this->assertSame('OPEN_X', $response['data']['open_id']);
        $this->assertStringNotContainsString('real-', $row->url . $row->response);
    }

    #[Test]
    public function test_redaction_masks_app_secret_in_url()
    {
        config()->set('tiktok.logging.redact_secrets', true);
        $this->exchange();

        $this->assertStringNotContainsString('real-secret', $this->row()->url);
        $this->assertStringContainsString('app_secret=%5BREDACTED%5D', $this->row()->url);
    }

    #[Test]
    public function test_redaction_keeps_real_auth_code_on_token()
    {
        config()->set('tiktok.logging.redact_secrets', true);
        $this->exchange();

        $this->assertSame('real-code', DB::table('tiktok_access_tokens')->value('code'));
        $this->assertSame('real-at', DB::table('tiktok_access_tokens')->value('access_token'));
    }

    #[Test]
    public function test_prunable_disabled_by_default()
    {
        TiktokRequest::create(['action' => 'X::y', 'url' => 'u']);
        DB::table('tiktok_requests')->update(['created_at' => now()->subDays(400)]);

        $this->assertSame(0, (new TiktokRequest)->prunable()->count());

        config()->set('tiktok.logging.prune_after_days', 0);
        $this->assertSame(0, (new TiktokRequest)->prunable()->count());
    }

    #[Test]
    public function test_prunable_selects_rows_older_than_configured_days()
    {
        config()->set('tiktok.logging.prune_after_days', 30);

        TiktokRequest::create(['action' => 'X::old', 'url' => 'u']);
        TiktokRequest::create(['action' => 'X::new', 'url' => 'u']);
        DB::table('tiktok_requests')->where('action', 'X::old')->update(['created_at' => now()->subDays(31)]);

        $this->assertSame(['X::old'], (new TiktokRequest)->prunable()->pluck('action')->all());
        $this->assertSame(1, (new TiktokRequest)->pruneAll());
        $this->assertSame(1, TiktokRequest::count());
    }

    #[Test]
    public function test_webhook_prunable_follows_config()
    {
        TiktokWebhook::create(['shop_id' => '1', 'type_id' => 1, 'event_type' => 'X', 'event_data' => []]);
        DB::table('tiktok_webhooks')->update(['created_at' => now()->subDays(400)]);

        $this->assertSame(0, (new TiktokWebhook)->prunable()->count());

        config()->set('tiktok.logging.prune_after_days', 30);
        $this->assertSame(1, (new TiktokWebhook)->prunable()->count());
    }

    #[Test]
    public function test_redact_array_returns_non_array_input_unchanged()
    {
        $service = (new TikTok())->auth();
        $method = new \ReflectionMethod($service, 'redactArray');

        foreach (['ok', 5, true, null] as $value) {
            $this->assertSame($value, $method->invoke($service, $value, ['access_token']));
        }
    }
}
