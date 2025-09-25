<?php

namespace Laraditz\TikTok\Services;

use TikTok;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laraditz\TikTok\Models\TiktokShop;
use Laraditz\TikTok\Models\TiktokRequest;
use Laraditz\TikTok\Models\TiktokAccessToken;
use Laraditz\TikTok\Exceptions\TikTokTokenException;

class AuthService extends BaseService
{

    public function afterAccessTokenRequest(TiktokRequest $request, array $result = []): void
    {
        $shop = DB::transaction(function () use ($request, $result) {
            $access_token = data_get($result, 'data.access_token');
            $access_token_expire_in = data_get($result, 'data.access_token_expire_in');
            $refresh_token = data_get($result, 'data.refresh_token');
            $refresh_token_expire_in = data_get($result, 'data.refresh_token_expire_in');
            $open_id = data_get($result, 'data.open_id');
            $seller_name = data_get($result, 'data.seller_name');
            $seller_base_region = data_get($result, 'data.seller_base_region');
            $user_type = data_get($result, 'data.user_type');
            $granted_scopes = data_get($result, 'data.granted_scopes');
            $timezone = config('app.timezone') ?? config('tiktok.defaut_timezone');

            throw_if(!$open_id, TikTokTokenException::class, __('Missing open_id'));

            $authorizedShopsResponse = TikTok::authorization(access_token: $access_token)->shops();
            $authorizedShops = data_get($authorizedShopsResponse, 'data.shops');

            $commonData = [
                'access_token' => $access_token,
                'refresh_token' => $refresh_token,
                'expires_at' => Carbon::createFromTimestamp($access_token_expire_in, $timezone),
                'refresh_expires_at' => Carbon::createFromTimestamp($refresh_token_expire_in, $timezone),
                'code' => data_get($request, 'code'),
            ];

            if ($authorizedShops && count($authorizedShops) > 0) {
                foreach ($authorizedShops as $authorizedShop) {
                    $shop = TiktokShop::updateOrCreate(
                        [
                            'identifier' => data_get($authorizedShop, 'id'),
                        ],
                        [
                            'open_id' => $open_id,
                            'code' => data_get($authorizedShop, 'code'),
                            'name' => data_get($authorizedShop, 'name'),
                            'region' => data_get($authorizedShop, 'region'),
                            'seller_type' => data_get($authorizedShop, 'seller_type'),
                            'cipher' => data_get($authorizedShop, 'cipher'),
                        ]
                    );

                    if ($shop->accessToken) {
                        $shop->accessToken->update($commonData);
                    } else {
                        $shop->accessToken()->create([
                            ...$commonData,
                            'open_id' => $open_id,
                            'seller_name' => $seller_name,
                            'seller_base_region' => $seller_base_region,
                            'user_type' => $user_type,
                            'granted_scopes' => $granted_scopes,
                        ]);
                    }
                }
            }
        });
    }

    public function beforeRefreshTokenRequest(): void
    {
        $shop = $this->tiktok->getShop();

        throw_if(!$shop->accessToken, TikTokTokenException::class, __('Missing access token.'));
        throw_if($shop->accessToken->refresh_expires_at < now(), TikTokTokenException::class, __('Refresh token has expired.'));

        $this->setQueryString(array_merge(
            $this->getQueryString(),
            [
                'app_secret' => $this->tiktok->getAppSecret(),
                'refresh_token' => $shop->accessToken->refresh_token,
                'grant_type' => 'refresh_token'
            ]
        ));
    }

    public function refreshAccessToken(TiktokAccessToken $accessToken): TiktokAccessToken
    {
        $this->routeName('auth.refresh_token')
            ->queryString([
                'app_key' => $this->tiktok->getAppKey(),
                'app_secret' => $this->tiktok->getAppSecret(),
                'refresh_token' => $accessToken->refresh_token,
                'grant_type' => 'refresh_token'
            ]);

        $result = $this->execute();
        $code = data_get($result, 'code');
        $data = data_get($result, 'data');

        if ($code === 0 && is_array($data) && count($data) > 0) {

            $updateData = $this->getRefreshTokenData($data);

            $accessToken->update($updateData->toArray());

            $accessToken->refresh();
        }

        return $accessToken;
    }

    public function afterRefreshTokenRequest(TiktokRequest $request, array $result = []): void
    {
        $shop = null;
        $data = data_get($result, 'data');
        $open_id = data_get($data, 'open_id');
        $seller_name = data_get($data, 'seller_name');

        if ($request->shop_id) {
            $shop = TiktokShop::where('identifier', $request->shop_id)->first();
        }

        if (!$shop && $seller_name) {
            $shop = TiktokShop::where('name', $seller_name)->first();
        }

        if (!$shop && $open_id) {
            $shop = TiktokShop::where('open_id', $open_id)->first();
        }

        if ($shop) {
            $updateData = $this->getRefreshTokenData($data);

            if ($shop->accessToken) {
                $shop->accessToken->update($updateData->toArray());
            } else {
                $shop->accessToken()->create($updateData->toArray());
            }
        }
    }

    private function getRefreshTokenData(array $data = []): Collection
    {
        $timezone = config('app.timezone') ?? config('tiktok.defaut_timezone');

        return collect($data)
            ->mapWithKeys(function ($value, $key) use ($timezone) {
                return match ($key) {
                    'access_token_expire_in' => ['expires_at' => Carbon::createFromTimestamp($value, $timezone)],
                    'refresh_token_expire_in' => ['refresh_expires_at' => Carbon::createFromTimestamp($value, $timezone)],
                    default => [$key => $value],
                };
            });
    }
}