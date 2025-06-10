<?php

namespace Laraditz\TikTok;

use Laraditz\TikTok\Models\TiktokAccessToken;
use Laraditz\TikTok\Models\TiktokShop;
use LogicException;
use BadMethodCallException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class TikTok
{
    private $services = ['auth', 'authorization', 'seller', 'order', 'product', 'event', 'return'];

    private ?TiktokShop $shop = null;

    private ?string $access_token = null;

    public function __construct(
        private string $app_key,
        private string $app_secret,
        private ?string $shop_id = null,
        private ?string $shop_code = null,
        private ?string $shop_name = null,
    ) {
    }

    public function __call($method, $arguments)
    {
        throw_if(!$this->getAppKey(), LogicException::class, __('Missing App Key.'));
        throw_if(!$this->getAppSecret(), LogicException::class, __('Missing App Secret.'));

        if (count($arguments) > 0) {
            $argumentCollection = collect($arguments);

            try {
                $argumentCollection->keys()->ensure('string');
            } catch (\Throwable $th) {
                // throw $th;
                throw new LogicException(__('Please pass a named arguments in :method method.', ['method' => $method]));
            }

            if ($access_token = data_get($arguments, 'access_token')) {
                $this->setAccessToken($access_token);
            }
        }

        $property_name = strtolower(Str::snake($method));

        if (in_array($property_name, $this->services)) {
            $reformat_property_name = ucfirst(Str::camel($method));

            $service_name = 'Laraditz\\TikTok\\Services\\' . $reformat_property_name . 'Service';

            return new $service_name(tiktok: app('tiktok'));
        } else {
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.',
                get_class(),
                $method
            ));
        }
    }

    public function getSignature(string $route, string $method, array $queryString = [], array $payload = []): string
    {
        $app_secret = $this->getAppSecret();
        $sign_method = $this->getSignMethod();

        $queryString = Arr::except($queryString, ['access_token', 'sign']);
        ksort($queryString);

        $data = urldecode(Arr::query($queryString));

        $data = $route . Str::remove(['=', '&'], $data);

        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $data .= json_encode($payload);
        }

        $data = $app_secret . $data . $app_secret;

        $signature = hash_hmac($sign_method, $data, $app_secret);

        return $signature;
    }

    public function getWebhookSignature(string $body): string
    {
        $app_key = $this->getAppKey();
        $app_secret = $this->getAppSecret();
        $sign_method = $this->getSignMethod();
        $base = $app_key . $body;

        $signature = hash_hmac($sign_method, $base, $app_secret);

        return $signature;
    }

    public function getAppKey(): string
    {
        return $this->app_key ?? config('tiktok.app_key');
    }

    public function getAppSecret(): string
    {
        return $this->app_secret ?? config('tiktok.app_secret');
    }

    public function getSignMethod(): string
    {
        return config('tiktok.sign_method');
    }

    public function getShopId(): ?string
    {
        return $this->shop_id ?? config('tiktok.shop_id');
    }

    public function getShopCode(): ?string
    {
        return $this->shop_code ?? config('tiktok.shop_code');
    }

    public function getShopName(): ?string
    {
        return $this->shop_name ?? config('tiktok.shop_name');
    }

    public function setShop(): void
    {
        if ($this->getAccessToken()) {
            $accessToken = TiktokAccessToken::where('access_token', $this->getAccessToken())->first();

            if ($accessToken) {
                $this->shop = $accessToken->subjectable;
            }
        }

        if (!$this->shop) {
            $shop_id = $this->shop_id ?? config('tiktok.shop_id');
            $shop_code = $this->shop_code ?? config('tiktok.shop_code');
            $shop_name = $this->shop_name ?? config('tiktok.shop_name');

            if ($shop_id) {
                $this->shop = TiktokShop::where('identifier', $shop_id)->first();
            }

            if (!$this->shop && $shop_code) {
                $this->shop = TiktokShop::where('code', $shop_code)->first();
            }

            // for first time after authorized, will use shop name to get the shop
            // as the api did not provide the shop id or code with access token
            if (!$this->shop && $shop_name) {
                $this->shop = TiktokShop::where('name', 'LIKE', $shop_name)->first();
            }

            if ($this->shop) {
                $this->setAccessToken($this->shop->accessToken?->access_token);
            }
        }
    }

    protected function setAccessToken(string $accessToken): void
    {
        $this->access_token = $accessToken;
    }

    public function getAccessToken(): ?string
    {
        return $this->access_token;
    }

    public function getShop(): ?TiktokShop
    {
        return $this->shop;
    }

    public function getShopCipher(): ?string
    {
        return $this->getShop()?->cipher;
    }
}
