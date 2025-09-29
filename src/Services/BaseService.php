<?php

namespace Laraditz\TikTok\Services;

use LogicException;
use BadMethodCallException;
use Illuminate\Support\Str;
use Laraditz\TikTok\TikTok;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Laraditz\TikTok\Models\TiktokRequest;
use Illuminate\Http\Client\RequestException;
use Laraditz\TikTok\Exceptions\TikTokAPIError;
use Laraditz\TikTok\Events\TikTokRequestFailed;

class BaseService
{
    public ?string $methodName = null;

    public ?string $serviceName = null;
    public ?string $fqcn = null;

    public function __construct(
        public TikTok $tiktok,
        private bool $shopCipher = false,
        private ?string $route = '',
        private ?string $method = 'get',
        private ?array $queryString = [], // for url query string
        private ?array $payload = [], // for body payload
        private null|array|string|int $params = null, // for path variables
    ) {
    }

    public function __call($methodName, $arguments)
    {
        $oClass = new \ReflectionClass(get_called_class());
        $fqcn = $oClass->getName();
        $this->fqcn = $fqcn;
        $this->setServiceName($oClass->getShortName());
        $this->setMethodName($methodName);

        if (!$this->tiktok->getShop()) {

            if (
                ($this->getServiceName() === 'AuthService' && $this->getMethodName() === 'accessToken')
                || ($this->getServiceName() === 'AuthorizationService' && $this->getMethodName() === 'shops')
            ) {
                // no need to set shop
            } else {
                $this->tiktok->checkShop();

                throw_if(
                    !$this->tiktok->getShop(),
                    TikTokAPIError::class,
                    ['code' => __('Error'), 'message' => __('Missing Shop ID.')]
                );
            }
        }

        // if method exists, return
        if (method_exists($this, $methodName)) {
            return $this->$methodName($arguments);
        }

        if (in_array(Str::snake($methodName), $this->getAllowedMethods())) {

            if (count($arguments) > 0) {
                $queryString = data_get($arguments, 'query');
                $body = data_get($arguments, 'body');
                $shopCipher = data_get($arguments, 'shop_cipher');
                $params = data_get($arguments, 'params');

                if ($queryString && is_array($queryString)) {
                    $this->setQueryString($queryString);
                }

                if ($body && is_array($body)) {
                    $this->setPayload($body);
                }

                if ($params) {
                    $this->setParams($params);
                }

                if ($shopCipher === true) {
                    $this->shopCipher = true;
                }
            }

            $this->setRouteFromConfig($fqcn, $methodName);

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
        $route_path = '';
        $params = $this->getParams();

        $route = config('tiktok.routes.' . $route_prefix . '.' . $route_name);

        $split = str($route)->explode(' ');

        if (count($split) == 2) {
            $this->setMethod(data_get($split, '0'));
            $route_path = data_get($split, '1');
        } elseif (count($split) == 1) {
            $route_path = data_get($split, '0');
        }

        if ($params) {
            if (is_array($params)) {
                $mappedParams = collect($params)->mapWithKeys(fn($value, $key) => ["{" . $key . "}" => $value]);

                $route_path = Str::swap($mappedParams->toArray(), $route_path);
            } elseif (is_string($params) || is_numeric($params)) {
                $route_path = str_replace('{id}', $params, $route_path);
            }
        }

        $this->setRoute($route_path);
    }

    protected function execute(): mixed
    {
        $oClass = new \ReflectionClass(get_called_class());
        if (!$this->getServiceName()) {
            $service_name = $oClass->getShortName();
            $this->setServiceName($service_name);
        }

        if (!$this->getMethodName()) {
            $called_method = $this->getCalledMethod();
            $this->setMethodName($called_method);
        }

        $method = $this->getMethod();
        $url = $this->getUrl();

        $client = Http::withHeaders($this->getHeaders())->asJson();

        $queryString = $this->getQueryString();
        $commonParameters = $this->getCommonParameters();

        $queryString = array_merge($commonParameters, $queryString);

        $this->setQueryString($queryString);

        $this->beforeRequest();

        if ($this->requireSignature()) {
            $signature = $this->tiktok->getSignature(
                route: $this->getRoute(),
                method: $method,
                queryString: $this->getQueryString(),
                payload: $this->getPayload()
            );

            throw_if(!$signature, LogicException::class, __('Failed to generate signature.'));

            $queryString = array_merge($this->getQueryString(), ['sign' => $signature]);
            $this->setQueryString($queryString);
        }

        $queryString = $this->getQueryString();

        // dd($queryString);

        if ($queryString && count($queryString) > 0) {
            $url = $url . '?' . http_build_query($queryString);
        }

        $payload = $this->getPayload();

        $request = TiktokRequest::create([
            'shop_id' => $this->tiktok->getShopId(),
            'action' => $this->getServiceName() . '::' . $this->getMethodName(),
            'url' => $url,
            'request' => $payload && count($payload) > 0 ? $payload : null,
        ]);

        $response = $payload && count($payload) > 0
            ? $client->$method($url, $payload)
            : $client->$method($url);

        // dd($response?->body(), $url, $payload, $this->tiktok->getShop());

        $response->throw(function (Response $response, RequestException $e) use ($request) {
            $result = $response->json();
            $message = data_get($result, 'message');

            $request->update([
                'code' => data_get($result, 'code'),
                'message' => $message ? Str::limit(trim($message), 255) : null,
                'error' => Str::limit(trim($e->getMessage()), 255),
            ]);

            $this->fireFailedEvents($message);
        });

        // dd($response->successful(), $response->failed(), $response->body(), $response->getStatusCode());

        $result = $response->json();

        if ($response->successful()) {
            $code = data_get($result, 'code');
            $message = data_get($result, 'message');

            $request->update([
                'code' => $code,
                'message' => $message,
                'response' => $result,
                'request_id' => data_get($result, 'request_id'),
                'error' => $code != '0' ? ($message ?? $code) : null
            ]);

            // success
            if ($code == '0') {
                $this->afterRequest($request, $result);

                return $result;
            }

            $this->fireFailedEvents($message);

            // http success but api request failed
            throw new TikTokAPIError($result ?? ['code' => __('Error')]);
        }

        if (!$request->error) {
            $request->update([
                'error' => __('API Server Error'),
            ]);
        }

        throw new TikTokAPIError(['code' => __('Error'), 'message' => __('API Server Error')]);
    }

    private function fireFailedEvents(?string $message = null)
    {
        event(new TikTokRequestFailed(
            fqcn: $this->fqcn,
            methodName: $this->getMethodName(),
            query: $this->getQueryString(),
            body: $this->getPayload(),
            message: $message
        ));
    }

    private function requireSignature(): bool
    {
        if ($this->getServiceName() === 'AuthService') {
            return false;
        }

        return true;
    }

    private function beforeRequest(): void
    {
        $methodName = 'before' . Str::studly($this->getMethodName()) . 'Request';

        if (method_exists($this, $methodName)) {
            $this->$methodName();
        }
    }

    private function afterRequest(TiktokRequest $request, array $result = []): void
    {
        $methodName = 'after' . Str::studly($this->getMethodName()) . 'Request';

        if (method_exists($this, $methodName)) {
            $this->$methodName($request, $result);
        }
    }

    public function getHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        $accessToken = $this->tiktok->getAccessToken();

        if ($this->getServiceName() === 'AuthService') {
            // no need access token for this service and method
        } elseif ($accessToken) {
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

        if (in_array($this->getServiceName(), ['AuthService', 'AuthorizationService', 'SellerService'])) {
            // no need shop cipher
        } elseif ($this->shopCipher === true) {
            $params['shop_cipher'] = $this->tiktok->getShopCipher();
        } else {
            $params['shop_cipher'] = $this->tiktok->getShopCipher();
        }

        return $params;
    }

    protected function getAllowedMethods(): array
    {
        $route_prefix = str($this->getServiceName())->remove('Service')->lower()->value;

        return array_keys(config('tiktok.routes.' . $route_prefix) ?? []);
    }

    protected function getUrl(): string
    {
        if (
            $this instanceof \Laraditz\TikTok\Services\AuthService
            && in_array($this->getMethodName(), ['accessToken', 'refreshToken', 'refreshAccessToken'])
        ) {
            $base_url = config('tiktok.auth_url');
        } else {
            $base_url = config('tiktok.base_url');
        }

        $url = $base_url . $this->getRoute();

        return $url;
    }

    protected function route(string $route): self
    {
        $this->setRoute($route);

        return $this;
    }

    protected function routeName(string $routeName): self
    {
        $this->setRoute($this->tiktok->getRoutePath($routeName));

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

    protected function setMethodName(string $methodName): void
    {
        $this->methodName = $methodName;
    }

    protected function getMethodName(): ?string
    {
        return $this->methodName;
    }

    protected function setServiceName(string $serviceName): void
    {
        $this->serviceName = $serviceName;
    }

    protected function getServiceName(): ?string
    {
        return $this->serviceName;
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

    protected function setParams(null|array|string|int $params): void
    {
        $this->params = $params;
    }

    protected function getParams(): null|array|string|int
    {
        return $this->params;
    }

    public function getCalledMethod()
    {
        $e = new \Exception();
        $trace = $e->getTrace();
        //position 0 would be the line that called this function so we ignore it
        return data_get($trace, '2.function');
    }

}
