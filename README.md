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

## Usage

```php
use TikTok;


$seller = TikTok::seller()->shops();

$products = TikTok::product()->search(
    shop_cipher: true,
    query: [
        'page_size' => 10
    ],
    body: [
        'status' => 'ALL'
    ]
);
```

## Event

This package also provide an event to allow your application to listen for TikTok webhook. You can create your listener and register it under event below.

| Event                                  | Description                         |
| -------------------------------------- | ----------------------------------- |
| Laraditz\TikTok\Events\WebhookReceived | Receive a push content from TikTok. |

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
