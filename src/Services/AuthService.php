<?php

namespace Laraditz\TikTok\Services;

use Illuminate\Support\Str;
use TikTok;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laraditz\TikTok\Models\TiktokShop;
use Laraditz\TikTok\Models\TiktokRequest;
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

            $shop = TiktokShop::updateOrCreate(
                [
                    'name' => $seller_name,
                ],
                [
                    'open_id' => $open_id,
                    'region' => $seller_base_region,
                ]
            );

            $commonData = [
                'access_token' => $access_token,
                'refresh_token' => $refresh_token,
                'expires_at' => Carbon::createFromTimestamp($access_token_expire_in, $timezone),
                'refresh_expires_at' => Carbon::createFromTimestamp($refresh_token_expire_in, $timezone),
                'code' => data_get($request, 'code'),
            ];

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

            return $shop;
        });

        try {
            $authorizedShops = TikTok::authorization(access_token: $shop->accessToken?->access_token)->shops();
        } catch (\Throwable $th) {
            // throw $th;
        }

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

    public function afterRefreshTokenRequest(TiktokRequest $request, array $result = []): void
    {
        $open_id = data_get($result, 'data.open_id');
        $seller_name = data_get($result, 'data.seller_name');
        $access_token = data_get($result, 'data.access_token');
        $access_token_expire_in = data_get($result, 'data.access_token_expire_in');
        $refresh_token = data_get($result, 'data.refresh_token');
        $refresh_token_expire_in = data_get($result, 'data.refresh_token_expire_in');
        $timezone = config('app.timezone') ?? config('tiktok.defaut_timezone');
        $seller_base_region = data_get($result, 'data.seller_base_region');
        $user_type = data_get($result, 'data.user_type');
        $granted_scopes = data_get($result, 'data.granted_scopes');
        $shop = null;

        if ($open_id) {
            $shop = TiktokShop::where('open_id', $open_id)->first();
        }

        if (!$shop) {
            $shop = TiktokShop::where('name', $seller_name)->first();
        }

        if ($shop) {
            $updateData = [
                'access_token' => $access_token,
                'refresh_token' => $refresh_token,
                'expires_at' => Carbon::createFromTimestamp($access_token_expire_in, $timezone),
                'refresh_expires_at' => Carbon::createFromTimestamp($refresh_token_expire_in, $timezone),
                'open_id' => $open_id,
                'seller_name' => $seller_name,
                'seller_base_region' => $seller_base_region,
                'user_type' => $user_type,
                'granted_scopes' => $granted_scopes,
            ];

            if ($shop->accessToken) {
                $shop->accessToken->update($updateData);
            } else {
                $shop->accessToken()->create($updateData);
            }
        }
    }
}