<?php

namespace Laraditz\TikTok\Tests\Unit\Services;

use Laraditz\TikTok\Tests\TestCase;
use Laraditz\TikTok\TikTok;
use Laraditz\TikTok\Services\BaseService;
use Laraditz\TikTok\Services\FinanceService;
use Illuminate\Support\Facades\Http;
use BadMethodCallException;

class FinanceServiceTest extends TestCase
{
    protected TikTok $tiktok;
    protected FinanceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tiktok = new TikTok();
        $shop = $this->createTikTokShop();
        $this->createAccessToken(['subjectable_id' => $shop->id]);
        $this->tiktok->setShop($shop);

        $this->service = new FinanceService($this->tiktok);
    }

    public function test_finance_service_extends_base_service()
    {
        $this->assertInstanceOf(BaseService::class, $this->service);
    }

    public function test_tiktok_finance_resolves_to_finance_service()
    {
        $this->assertInstanceOf(FinanceService::class, $this->tiktok->finance());
    }

    public function test_throws_exception_for_unconfigured_method()
    {
        $this->expectException(BadMethodCallException::class);

        $this->service->notConfigured();
    }

    public function test_can_call_statements_method()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => [
                    'statements' => [
                        ['id' => 'statement_1', 'statement_time' => 1700000000],
                    ],
                ],
            ]),
        ]);

        $result = $this->service->statements(
            query: ['page_size' => 10, 'sort_field' => 'statement_time']
        );

        $this->assertIsArray($result);
        $this->assertEquals('0', $result['code']);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(1, $result['data']['statements']);
    }

    public function test_statements_sends_correct_request()
    {
        Http::fake([
            '*' => Http::response(['code' => '0', 'message' => 'Success', 'data' => []]),
        ]);

        $this->service->statements(
            query: ['page_size' => 10, 'sort_field' => 'statement_time']
        );

        Http::assertSent(function ($request) {
            $url = $request->url();

            return $request->method() === 'GET' &&
                str_contains($url, '/finance/202309/statements') &&
                str_contains($url, 'page_size=10') &&
                str_contains($url, 'app_key=test_app_key') &&
                str_contains($url, 'shop_cipher=test_cipher') &&
                str_contains($url, 'sign=');
        });

        $this->assertDatabaseHas('tiktok_requests', [
            'shop_id' => 'test_shop_id',
            'action' => 'FinanceService::statements',
            'code' => '0',
        ]);
    }

    public function test_can_call_payments_method()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => [
                    'payments' => [
                        ['id' => 'payment_1', 'status' => 'PAID'],
                    ],
                ],
            ]),
        ]);

        $result = $this->service->payments(
            query: ['page_size' => 10]
        );

        $this->assertIsArray($result);
        $this->assertEquals('0', $result['code']);
        $this->assertCount(1, $result['data']['payments']);
    }

    public function test_payments_sends_correct_request()
    {
        Http::fake([
            '*' => Http::response(['code' => '0', 'message' => 'Success', 'data' => []]),
        ]);

        $this->service->payments(query: ['page_size' => 10]);

        Http::assertSent(function ($request) {
            $url = $request->url();

            return $request->method() === 'GET' &&
                str_contains($url, '/finance/202605/payments') &&
                str_contains($url, 'page_size=10') &&
                str_contains($url, 'shop_cipher=test_cipher') &&
                str_contains($url, 'sign=');
        });
    }

    public function test_can_call_withdrawals_method()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => [
                    'withdrawals' => [
                        ['id' => 'withdrawal_1', 'type' => 'WITHDRAW'],
                    ],
                ],
            ]),
        ]);

        $result = $this->service->withdrawals(
            query: ['page_size' => 10]
        );

        $this->assertIsArray($result);
        $this->assertEquals('0', $result['code']);
        $this->assertCount(1, $result['data']['withdrawals']);
    }

    public function test_withdrawals_sends_correct_request()
    {
        Http::fake([
            '*' => Http::response(['code' => '0', 'message' => 'Success', 'data' => []]),
        ]);

        $this->service->withdrawals(query: ['page_size' => 10]);

        Http::assertSent(function ($request) {
            $url = $request->url();

            return $request->method() === 'GET' &&
                str_contains($url, '/finance/202309/withdrawals') &&
                str_contains($url, 'page_size=10') &&
                str_contains($url, 'shop_cipher=test_cipher') &&
                str_contains($url, 'sign=');
        });
    }

    public function test_can_call_unsettled_transactions_method()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => [
                    'order_transactions' => [
                        ['order_id' => 'order_1'],
                    ],
                ],
            ]),
        ]);

        $result = $this->service->unsettledTransactions(
            query: ['page_size' => 10]
        );

        $this->assertIsArray($result);
        $this->assertEquals('0', $result['code']);
        $this->assertCount(1, $result['data']['order_transactions']);
    }

    public function test_unsettled_transactions_sends_correct_request()
    {
        Http::fake([
            '*' => Http::response(['code' => '0', 'message' => 'Success', 'data' => []]),
        ]);

        $this->service->unsettledTransactions(query: ['page_size' => 10]);

        Http::assertSent(function ($request) {
            $url = $request->url();

            return $request->method() === 'GET' &&
                str_contains($url, '/finance/202507/orders/unsettled') &&
                str_contains($url, 'page_size=10') &&
                str_contains($url, 'shop_cipher=test_cipher') &&
                str_contains($url, 'sign=');
        });
    }

    public function test_can_call_transactions_by_order_method()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => [
                    'transactions' => [
                        ['id' => 'transaction_1'],
                    ],
                ],
            ]),
        ]);

        $result = $this->service->transactionsByOrder(
            params: ['order_id' => 'order_123']
        );

        $this->assertIsArray($result);
        $this->assertEquals('0', $result['code']);
        $this->assertCount(1, $result['data']['transactions']);
    }

    public function test_transactions_by_order_sends_correct_request()
    {
        Http::fake([
            '*' => Http::response(['code' => '0', 'message' => 'Success', 'data' => []]),
        ]);

        $this->service->transactionsByOrder(
            params: ['order_id' => 'order_123'],
            query: ['page_size' => 10]
        );

        Http::assertSent(function ($request) {
            $url = $request->url();

            return $request->method() === 'GET' &&
                str_contains($url, '/finance/202501/orders/order_123/statement_transactions') &&
                str_contains($url, 'page_size=10') &&
                str_contains($url, 'shop_cipher=test_cipher') &&
                str_contains($url, 'sign=');
        });
    }

    public function test_can_call_transactions_by_statement_method()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => [
                    'transactions' => [
                        ['id' => 'transaction_1'],
                    ],
                ],
            ]),
        ]);

        $result = $this->service->transactionsByStatement(
            params: ['statement_id' => 'statement_456']
        );

        $this->assertIsArray($result);
        $this->assertEquals('0', $result['code']);
        $this->assertCount(1, $result['data']['transactions']);
    }

    public function test_transactions_by_statement_sends_correct_request()
    {
        Http::fake([
            '*' => Http::response(['code' => '0', 'message' => 'Success', 'data' => []]),
        ]);

        $this->service->transactionsByStatement(
            params: ['statement_id' => 'statement_456'],
            query: ['page_size' => 10]
        );

        Http::assertSent(function ($request) {
            $url = $request->url();

            return $request->method() === 'GET' &&
                str_contains($url, '/finance/202501/statements/statement_456/statement_transactions') &&
                str_contains($url, 'page_size=10') &&
                str_contains($url, 'shop_cipher=test_cipher') &&
                str_contains($url, 'sign=');
        });
    }
}
