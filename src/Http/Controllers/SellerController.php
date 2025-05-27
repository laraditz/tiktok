<?php

namespace Laraditz\TikTok\Http\Controllers;

use Illuminate\Http\Request;
use Laraditz\TikTok\Exceptions\TikTokException;
use TikTok;

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

            $shop = TikTok::getShop();

            return view('tiktok::sellers.authorized', [
                'code' => $code,
                'shop' => $shop,
            ]);
        } catch (\Throwable $th) {
            // dd($th->getMessage());
            throw $th;
        }
    }
}
