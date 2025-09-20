<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    'app_key' => env('TIKTOK_APP_KEY'),
    'app_secret' => env('TIKTOK_APP_SECRET'),
    'grant_type' => 'authorized_code',
    'sign_method' => env('TIKTOK_SIGN_METHOD', 'sha256'),
    'default_timezone' => env('TIKTOK_DEFAULT_TIMEZONE', 'UTC'),
    'shop_id' => env('TIKTOK_SHOP_ID'),
    'shop_code' => env('TIKTOK_SHOP_CODE'),
    'shop_name' => env('TIKTOK_SHOP_NAME'),
    'auth_url' => 'https://auth.tiktok-shops.com',
    'base_url' => 'https://open-api.tiktokglobalshop.com',
    'routes' => [
        'prefix' => 'tiktok',
        'auth' => [
            'access_token' => '/api/v2/token/get',
            'refresh_token' => '/api/v2/token/refresh',
        ],
        'authorization' => [
            'shops' => '/authorization/202309/shops'
        ],
        'event' => [
            'webhook_list' => '/event/202309/webhooks',
            'update_webhook' => 'PUT /event/202309/webhooks',
            'delete_webhook' => 'DELETE /event/202309/webhooks',
        ],
        'seller' => [
            'shops' => '/seller/202309/shops'
        ],
        'order' => [
            'list' => 'POST /order/202309/orders/search',
            'detail' => '/order/202309/orders',
            'price_detail' => '/order/202407/orders/{order_id}/price_detail',
            'external_orders' => '/order/202406/orders/{order_id}/external_orders',
            'add_external_orders' => 'POST /order/202406/orders/external_orders',
            'search_external_orders' => 'POST /order/202406/orders/external_order_search',
        ],
        'product' => [
            'get' => '/product/202309/products/{product_id}',
            'list' => 'POST /product/202502/products/search',
            'update_inventory' => 'POST /product/202309/products/{product_id}/inventory/update',
        ],
        'return' => [
            'get' => '/return_refund/202309/returns/{return_id}/records',
            'list' => 'POST /return_refund/202309/returns/search',
        ],
    ],
    'middleware' => ['api'],
];