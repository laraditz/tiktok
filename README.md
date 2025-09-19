# Laravel TikTok

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laraditz/tiktok.svg?style=flat-square)](https://packagist.org/packages/laraditz/tiktok)
[![Total Downloads](https://img.shields.io/packagist/dt/laraditz/tiktok.svg?style=flat-square)](https://packagist.org/packages/laraditz/tiktok)
![GitHub Actions](https://github.com/laraditz/tiktok/actions/workflows/main.yml/badge.svg)

Laravel package for interacting with TikTok API.

## Requirements

- PHP 8.2 and above.
- Laravel 10 and above.

## Installation

You can install the package via composer:

```bash
composer require laraditz/tiktok
```

## Before Start

Configure your variables in your `.env` (recommended) or you can publish the config file and change it there.

```
TIKTOK_APP_KEY=<your_tiktok_app_key>
TIKTOK_APP_SECRET=<your_tiktok_app_secret>
TIKTOK_SHOP_ID=<your_tiktok_shop_id>
TIKTOK_SHOP_CODE=MYXXXXXXXX
TIKTOK_SHOP_NAME=<your_tiktok_shop_name>
```

(Optional) You can publish the config file via this command:

```bash
php artisan vendor:publish --provider="Laraditz\TikTok\TikTokServiceProvider" --tag="config"
```

Run the migration command to create the necessary database table.

```bash
php artisan migrate
```

On TikTok Shop Partner Center, configure this **Redirect URL** on your App Management section. Once seller has authorized the app, it will redirect to this URL. Under the hood, it will call API to generate access token so that you do not have to call it manually.

```
// App Callback URL
https://your-app-url.com/tiktok/seller/authorized
```

## Available Methods

Below are all methods available under this package. Parameters for all method calls will follow exactly as in [TikTok Shop API Documentation](https://partner.tiktokshop.com/docv2/page/6789f6f818828103147a8b05). `app_key`, `sign`, `timestamp`, `shop_cipher` are common parameters and will be append automatically when required.

### Authentication Service `auth()`

| Method                 | Description                                   | Parameters                                                |
| ---------------------- | --------------------------------------------- | --------------------------------------------------------- |
| `accessToken()`        | Generate access token from authorization code | query: `app_key`, `app_secret`, `auth_code`, `grant_type` |
| `refreshAccessToken()` | Refresh access token before it expired.       | TiktokAccessToken `tiktokAccessToken`                     |

### Authorization Service `authorization()`

| Method    | Description                                                          |
| --------- | -------------------------------------------------------------------- |
| `shops()` | Retrieves the list of shops that a seller has authorized for an app. |

### Event Service `event()`

| Method            | Description                                                     | Parameters                    |
| ----------------- | --------------------------------------------------------------- | ----------------------------- |
| `webhookList()`   | Retrieves a shop's webhooks and the corresponding webhook URLs. |                               |
| `updateWebhook()` | Updates the shop's webhook URL for a specific event topic.      | body: `event_type`, `address` |
| `deleteWebhook()` | Deletes the shop's webhook URL for a specific event topic.      | body: `event_type`            |

### Seller Service `seller()`

| Method    | Description                                         |
| --------- | --------------------------------------------------- |
| `shops()` | Retrieves all active shops that belong to a seller. |

### Order Service `order()`

Full parameters refer to [API documentation](https://partner.tiktokshop.com/docv2/page/order-api-overview)

| Method          | Description                                                                                                | Parameters                    |
| --------------- | ---------------------------------------------------------------------------------------------------------- | ----------------------------- |
| `list()`        | Returns a list of orders created or updated during the timeframe indicated by the specified parameters.    | query: `page_size` and more   |
|                 |                                                                                                            | body: `order_status` and more |
| `detail()`      | Get the detailed order information of an order.                                                            | query: `ids`                  |
| `priceDetail()` | Get the detailed pricing calculation information of an order or a line item, including vouchers, tax, etc. | params: `order_id`            |

### Product Service `product()`

Full parameters refer to [API documentation](https://partner.tiktokshop.com/docv2/page/products-api-overview)

| Method   | Description                                                                             | Parameters                                |
| -------- | --------------------------------------------------------------------------------------- | ----------------------------------------- |
| `list()` | Retrieve a list of products that meet the specified conditions.                         | query: `page_size`, `page_token`          |
|          |                                                                                         | body: `status`, `update_time_ge` and more |
| `get()`  | Retrieve all properties of a product that is in the DRAFT, PENDING, or ACTIVATE status. | params: `product_id`                      |

### Return Service `return()`

Full parameters refer to [API documentation](https://partner.tiktokshop.com/docv2/page/return-refund-and-cancel-api-overview)

| Method   | Description                                   | Parameters                                |
| -------- | --------------------------------------------- | ----------------------------------------- |
| `list()` | Use this API to retrieve one or more returns. | query: `page_size`, `page_token` and more |
| `get()`  | Use this API to get a list of return records. | params: `return_id`                       |

## Usage

```php
// Using service container
$seller = app('tiktok')->seller()->shops();

// Using facade
$products = \TikTok::product()->list(
    query: [
        'page_size' => 10
    ],
    body: [
        'status' => 'ALL'
    ]
);

// Pass path variables to params
// e.g. path: /return_refund/202309/returns/{return_id}/records
$returnOrders = TikTok::return()->get(
    params: [
        'return_id' => '1681299342034327'
    ],
);
```

## Event

This package also provide an event to allow your application to listen for TikTok webhook. You can create your listener and register it under event below.

| Event                                      | Description                                  |
| ------------------------------------------ | -------------------------------------------- |
| Laraditz\TikTok\Events\WebhookReceived     | Receive a push content from TikTok.          |
| Laraditz\TikTok\Events\TikTokRequestFailed | Trigger when a request to TikTok API failed. |

Read more about TikTok Webhooks [here](https://partner.tiktokshop.com/docv2/page/64f1997e93f5dc028e357341).

## Webhook URL

You may setup the Callback URL below on TikTok Shop API dashboard, under the Manage App section so that TikTok will push all content update to this url and trigger the `WebhookReceived` event above.

```
https://your-app-url.com/tiktok/webhooks/all
```

You can also register individual webhook for specific event like so:-

```php
$webhooks = TikTok::event()->updateWebhook(
    shop_cipher: true,
    body: [
        'event_type' => 'ORDER_STATUS_CHANGE',
        'address' => 'https://your-app-url.com/tiktok/webhooks/order-status-change',
    ]
);
```

## Commands

```bash
tiktok:flush-expired-token    Flush expired access token.
tiktok:refresh-token          Refresh existing access token before it expired.
```

As TikTok access token has an expired date, you may want to set `tiktok:refresh-token` on scheduler and run it before it expires to refresh the access token. Otherwise, you need the seller to reauthorize and generate a new access token.

#### Token Duration

- Access token: 7 days
- Refresh token: +-2 months

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email raditzfarhan@gmail.com instead of using the issue tracker.

## Credits

- [Raditz Farhan](https://github.com/laraditz)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
