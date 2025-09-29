<?php

namespace Laraditz\TikTok\Http\Controllers;

use Laraditz\TikTok\Models\TiktokAccessToken;
use TikTok;
use Illuminate\Http\Request;
use Laraditz\TikTok\Models\TiktokShop;
use Laraditz\TikTok\Exceptions\TikTokException;

class SellerController extends Controller
{
    public function authorized(Request $request)
    {
        $code = $request->code;

        throw_if(!$code, TikTokException::class, __('Missing code.'));

        $tiktok = app('tiktok');

        try {

            $accessToken = TikTok::auth()->accessToken(
                query: [
                    'app_key' => $tiktok->getAppKey(),
                    'app_secret' => $tiktok->getAppSecret(),
                    'auth_code' => $code,
                    'grant_type' => 'authorized_code'
                ]
            );

            $access_token = data_get($accessToken, 'data.access_token');

            $accessTokens = TiktokAccessToken::where('access_token', $access_token)->get();

            return view('tiktok::sellers.authorized', [
                'code' => $code,
                'accessTokens' => $accessTokens,
            ]);
        } catch (\Throwable $th) {
            // dd($th->getMessage());
            throw $th;
        }
    }
}
