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

            // $accessToken = [
            //     "code" => 0,
            //     "message" => "success",
            //     "data" => [
            //         "access_token" => "ROW_W0TWygAAAABVf0WqZWujELIiZDCx6rrZuoU-kJBBG7ppa-3iJDHq4SCtlf_eHajZHQDHWqCuFMZ_r_sRh4nwl3AsYALEa5ZNFsNHO-eNQ8Ix3dpRGojsv4hgP-8b6zwJJLVVa-J_rn5psTUdVPcASCGkDkYX4lFk9LSVuogGp0Qkp9UATIXPiw",
            //         "access_token_expire_in" => 1759388714,
            //         "refresh_token" => "ROW_xPwCzwAAAADBAEU5LcdKjEBz-ZT0-961L-H-iPoskvXwj4tI6_P_EbSSJprcbN4ZpXKYnOObZpY",
            //         "refresh_token_expire_in" => 1766056857,
            //         "open_id" => "BweoCQAAAAAvYiJymwCnvqJevbuTsEoCNVic61j_nFk2MdCkVbNJug",
            //         "seller_name" => "SANDBOX7551752395413899015",
            //         "seller_base_region" => "MY",
            //         "user_type" => 0,
            //         "granted_scopes" => [
            //             0 => "seller.global_product.delete",
            //             1 => "seller.global_product.info",
            //             2 => "seller.global_product.write",
            //             3 => "seller.product.write",
            //             4 => "seller.delivery.status.write",
            //             5 => "seller.order.info",
            //             6 => "seller.fulfillment.basic",
            //             7 => "seller.global_product.category.info",
            //             8 => "seller.return_refund.basic",
            //             9 => "seller.product.basic",
            //             10 => "seller.logistics",
            //             11 => "seller.shop.info",
            //             12 => "seller.authorization.info",
            //             13 => "seller.finance.info",
            //             14 => "data.shop_analytics.public.read",
            //         ]
            //     ],
            //     "request_id" => "202509251505148F4D84AA40419000154F",
            // ];

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
