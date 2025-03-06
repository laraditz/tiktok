<?php

namespace Laraditz\TikTok\Services;

use BadMethodCallException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laraditz\TikTok\Exceptions\TikTokAPIError;
use Laraditz\TikTok\Models\TiktokRequest;
use Laraditz\TikTok\TikTok;
use LogicException;

class BaseService
{
    public string $methodName;

    public string $serviceName;

    public function __construct(
        public TikTok $tiktok,
        private ?string $route = '',
        private ?string $method = 'get',
        private ?array $queryString = [],
        private ?array $payload = [],
    ) {
    }

    public function __call($methodName, $arguments)
    {
        $oClass = new \ReflectionClass(get_called_class());
        $fqcn = $oClass->getName();
        $this->serviceName = $oClass->getShortName();
        $this->methodName = $methodName;

        // if method exists, return
        if (method_exists($this, $methodName)) {
            return $this->$methodName($arguments);
        }

        if (in_array(Str::snake($methodName), $this->getAllowedMethods())) {
            $this->setRouteFromConfig($fqcn, $methodName);

            if (count($arguments) > 0) {
                $this->setPayload($arguments);
            }

            return $this->execute();
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.',
            $fqcn,
            $methodName
        ));
    }

    private function setRouteFromConfig(string $fqcn, string $method): void
    {
        $route_prefix = str($fqcn)->afterLast('\\')->remove('Service')->lower()->value;
        $route_name = str($method)->snake()->value;

        $route = config('tiktok.routes.' . $route_prefix . '.' . $route_name);

        $split = str($route)->explode(' ');

        if (count($split) == 2) {
            $this->setMethod(data_get($split, '0'));
            $this->setRoute(data_get($split, '1'));
        } elseif (count($split) == 1) {
            $this->setRoute(data_get($split, '0'));
        }
    }

    protected function execute()
    {
        $method = $this->getMethod();
        $url = $this->getUrl();

        $response = Http::withHeaders($this->getHeaders())->asJson();

        $payload = $this->getPayload();

        $commonParameters = $this->getCommonParameters();

        $payload = array_merge($commonParameters, $payload);

        $signature = $this->tiktok->getSignature($this->getRoute(), $payload);

        throw_if(!$signature, LogicException::class, __('Failed to generate signature.'));

        $payload = array_merge($payload, ['sign' => $signature]);

        $request = TiktokRequest::create([
            'action' => $this->serviceName . '::' . $this->methodName,
            'url' => $url,
            'request' => $payload,
        ]);

        $response = $response->$method($url, $payload);

        $response->throw(function (Response $response, RequestException $e) use ($request) {
            $result = $response->json();
            $message = data_get($result, 'message');

            $request->update([
                'code' => data_get($result, 'code'),
                'message' => $message ? Str::limit(trim($message), 255) : null,
                'error' => Str::limit(trim($e->getMessage()), 255),
            ]);
        });

        $result = $response->json();

        if ($response->successful()) {
            $code = data_get($result, 'code');

            $request->update([
                'code' => $code,
                'message' => data_get($result, 'message'),
                'response' => $result,
                'request_id' => data_get($result, 'request_id'),
                'error' => $code != '0' ? (data_get($result, 'message') ?? data_get($result, 'code')) : null
            ]);

            // success
            if ($code == '0') {

                $this->afterRequest($request, $result);

                return $result;
            }

            // http success but api request failed
            throw new TikTokAPIError($result ?? ['code' => __('Error')]);
        }

        $request->update([
            'error' => __('API Server Error'),
        ]);

        throw new TikTokAPIError(['code' => __('Error'), 'message' => __('API Server Error')]);
    }

    private function afterRequest(TiktokRequest $request, array $result = []): void
    {
        $methodName = 'after' . Str::studly($this->methodName) . 'Request';

        if (method_exists($this, $methodName)) {
            $this->$methodName($request, $result);
        }
    }

    public function getHeaders(): array
    {
        $headers = [
            'content-type' => 'application/json',
        ];

        $accessToken = $this->tiktok->getAccessToken();

        if ($accessToken) {
            $headers['x-tts-access-token'] = $accessToken;
        }

        return $headers;
    }

    public function getCommonParameters(): array
    {
        $params = [
            'app_key' => $this->tiktok->getAppKey(),
            'timestamp' => now()->getTimestamp(),
            // 'sign_method' => $this->tiktok->getSignMethod(),
        ];



        return $params;
    }

    protected function getAllowedMethods(): array
    {
        $route_prefix = str($this->serviceName)->remove('Service')->lower()->value;

        return array_keys(config('tiktok.routes.' . $route_prefix) ?? []);
    }

    protected function getUrl(): string
    {
        if (
            $this instanceof \Laraditz\TikTok\Services\AuthService
            && in_array($this->methodName, ['accessToken', 'refreshToken'])
        ) {
            $base_url = config('tiktok.auth_url');
        } else {
            $base_url = config('tiktok.base_url');
        }

        return $base_url . $this->getRoute();
    }

    protected function route(string $route): self
    {
        $this->setRoute($route);

        return $this;
    }

    protected function setRoute(string $route): void
    {
        $this->route = $route;
    }

    protected function getRoute(): string
    {
        return $this->route;
    }

    protected function method(string $method): self
    {
        $this->setMethod($method);

        return $this;
    }

    protected function setMethod(string $method): void
    {
        if ($method) {
            $this->method = strtolower($method);
        }
    }

    protected function getMethod(): string
    {
        return $this->method;
    }

    public function payload(array $payload): self
    {
        $this->setPayload($payload);

        return $this;
    }

    protected function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    protected function getPayload(): array
    {
        return $this->payload;
    }

    public function queryString(array $queryString): self
    {
        $this->setQueryString($queryString);

        return $this;
    }

    protected function setQueryString(array $queryString): void
    {
        $this->queryString = $queryString;
    }

    protected function getQueryString(): array
    {
        return $this->queryString;
    }

}
