<?php

namespace Laraditz\TikTok\Tests\Unit\Services;

use Laraditz\TikTok\Tests\TestCase;
use Laraditz\TikTok\TikTok;
use Laraditz\TikTok\Services\BaseService;
use Laraditz\TikTok\Services\ProductService;
use Laraditz\TikTok\Models\TiktokRequest;
use Laraditz\TikTok\Exceptions\TikTokAPIError;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use BadMethodCallException;

class BaseServiceTest extends TestCase
{
    protected TikTok $tiktok;
    protected ProductService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tiktok = new TikTok();
        $shop = $this->createTikTokShop();
        $this->createAccessToken(['subjectable_id' => $shop->id]);
        $this->tiktok->setShop($shop);

        $this->service = new ProductService($this->tiktok);
    }

    public function test_base_service_requires_tiktok_instance()
    {
        $service = new ProductService($this->tiktok);

        $this->assertInstanceOf(BaseService::class, $service);
        $this->assertSame($this->tiktok, $service->tiktok);
    }

    public function test_service_can_set_and_get_route()
    {
        $service = new class($this->tiktok) extends BaseService {
            public function testRoute(string $route)
            {
                return $this->route($route);
            }

            public function testGetRoute()
            {
                return $this->getRoute();
            }

            protected function getAllowedMethods(): array
            {
                return ['test'];
            }
        };

        $result = $service->testRoute('/test/route');

        $this->assertSame($service, $result);
        $this->assertEquals('/test/route', $service->testGetRoute());
    }

    public function test_service_can_set_and_get_method()
    {
        $service = new class($this->tiktok) extends BaseService {
            public function testMethod(string $method)
            {
                return $this->method($method);
            }

            public function testGetMethod()
            {
                return $this->getMethod();
            }

            protected function getAllowedMethods(): array
            {
                return ['test'];
            }
        };

        $result = $service->testMethod('POST');

        $this->assertSame($service, $result);
        $this->assertEquals('post', $service->testGetMethod());
    }

    public function test_service_can_set_and_get_payload()
    {
        $service = new class($this->tiktok) extends BaseService {
            public function testPayload(array $payload)
            {
                return $this->payload($payload);
            }

            public function testGetPayload()
            {
                return $this->getPayload();
            }

            protected function getAllowedMethods(): array
            {
                return ['test'];
            }
        };

        $payload = ['key' => 'value'];
        $result = $service->testPayload($payload);

        $this->assertSame($service, $result);
        $this->assertEquals($payload, $service->testGetPayload());
    }

    public function test_service_can_set_and_get_query_string()
    {
        $service = new class($this->tiktok) extends BaseService {
            public function testQueryString(array $queryString)
            {
                return $this->queryString($queryString);
            }

            public function testGetQueryString()
            {
                return $this->getQueryString();
            }

            protected function getAllowedMethods(): array
            {
                return ['test'];
            }
        };

        $queryString = ['param' => 'value'];
        $result = $service->testQueryString($queryString);

        $this->assertSame($service, $result);
        $this->assertEquals($queryString, $service->testGetQueryString());
    }

    public function test_service_generates_common_parameters()
    {
        $service = new class($this->tiktok) extends BaseService {
            public function testGetCommonParameters()
            {
                return $this->getCommonParameters();
            }

            protected function getAllowedMethods(): array
            {
                return ['test'];
            }
        };

        $commonParams = $service->testGetCommonParameters();

        $this->assertArrayHasKey('app_key', $commonParams);
        $this->assertArrayHasKey('timestamp', $commonParams);
        $this->assertArrayHasKey('shop_cipher', $commonParams);
        $this->assertEquals('test_app_key', $commonParams['app_key']);
    }

    public function test_service_generates_headers_with_access_token()
    {
        $service = new class($this->tiktok) extends BaseService {
            public function testGetHeaders()
            {
                return $this->getHeaders();
            }

            protected function getAllowedMethods(): array
            {
                return ['test'];
            }
        };

        $this->tiktok->setAccessToken('test_access_token');
        $headers = $service->testGetHeaders();

        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('x-tts-access-token', $headers);
        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertEquals('test_access_token', $headers['x-tts-access-token']);
    }

    public function test_service_generates_correct_url_for_auth_service()
    {
        $authService = new \Laraditz\TikTok\Services\AuthService($this->tiktok);

        // Use reflection to access protected methods and properties
        $reflection = new \ReflectionClass($authService);

        // Get parent class properties since route is in BaseService
        $parentReflection = $reflection->getParentClass();
        $routeProperty = $parentReflection->getProperty('route');
        $routeProperty->setAccessible(true);
        $routeProperty->setValue($authService, '/api/v2/token/get');

        $methodNameProperty = $parentReflection->getProperty('methodName');
        $methodNameProperty->setAccessible(true);
        $methodNameProperty->setValue($authService, 'accessToken');

        $getUrlMethod = $parentReflection->getMethod('getUrl');
        $getUrlMethod->setAccessible(true);

        $url = $getUrlMethod->invoke($authService);

        $this->assertStringStartsWith('https://auth.tiktok-shops.com', $url);
        $this->assertStringEndsWith('/api/v2/token/get', $url);
    }

    public function test_service_generates_correct_url_for_api_service()
    {
        $service = new class($this->tiktok) extends BaseService {
            public function testGetUrl()
            {
                $this->setRoute('/product/202309/products');
                return $this->getUrl();
            }

            protected function getAllowedMethods(): array
            {
                return ['list'];
            }
        };

        $url = $service->testGetUrl();

        $this->assertStringStartsWith('https://open-api.tiktokglobalshop.com', $url);
        $this->assertStringEndsWith('/product/202309/products', $url);
    }

    public function test_throws_exception_for_invalid_method()
    {
        $this->expectException(BadMethodCallException::class);

        $this->service->invalidMethod();
    }

    public function test_service_can_handle_named_arguments()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '0',
                'message' => 'Success',
                'data' => []
            ])
        ]);

        $service = new class($this->tiktok) extends BaseService {
            protected function getAllowedMethods(): array
            {
                return ['test_method'];
            }
        };

        // Mock the method exists to simulate config route
        config(['tiktok.routes.baseservice.test_method' => 'GET /test/route']);

        $result = $service->testMethod(
            query: ['page_size' => 10],
            body: ['status' => 'ACTIVE'],
            shop_cipher: true
        );

        $this->assertIsArray($result);
    }


    public function test_service_handles_api_error_response()
    {
        Http::fake([
            '*' => Http::response([
                'code' => '1001',
                'message' => 'Invalid request',
                'data' => null
            ])
        ]);

        $service = new class($this->tiktok) extends BaseService {
            protected function getAllowedMethods(): array
            {
                return ['test_method'];
            }
        };

        config(['tiktok.routes.baseservice.test_method' => 'GET /test/route']);

        $this->expectException(TikTokAPIError::class);

        $service->testMethod();
    }

    public function test_service_handles_http_error_response()
    {
        Http::fake([
            '*' => Http::response(null, 500)
        ]);

        $service = new class($this->tiktok) extends BaseService {
            protected function getAllowedMethods(): array
            {
                return ['test_method'];
            }
        };

        config(['tiktok.routes.baseservice.test_method' => 'GET /test/route']);

        $this->expectException(\Illuminate\Http\Client\RequestException::class);

        $service->testMethod();
    }
}