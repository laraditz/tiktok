# Laravel TikTok

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laraditz/tiktok.svg?style=flat-square)](https://packagist.org/packages/laraditz/tiktok)
[![Total Downloads](https://img.shields.io/packagist/dt/laraditz/tiktok.svg?style=flat-square)](https://packagist.org/packages/laraditz/tiktok)
[![License](https://img.shields.io/packagist/l/laraditz/tiktok?style=flat-square)](./LICENSE.md)
![GitHub Actions](https://github.com/laraditz/tiktok/actions/workflows/main.yml/badge.svg)

A comprehensive Laravel package for seamless integration with the TikTok Shop API. This package provides a clean, intuitive interface for managing TikTok shops, handling authentication, processing orders, managing products, and receiving webhooks.

<a href="https://www.buymeacoffee.com/raditzfarhan" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/v2/default-yellow.png" alt="Buy Me A Coffee" style="height: 50px !important;width: 200px !important;" ></a>

## Features

- 🔐 **Complete Authentication Flow** - Automatic token management with refresh capabilities
- 🏪 **Multi-Shop Support** - Manage multiple TikTok shops within a single application
- 📦 **Product Management** - Full CRUD operations for TikTok Shop products
- 🛒 **Order Processing** - Comprehensive order management and tracking
- 🔄 **Return Handling** - Complete return and refund management
- 📡 **Webhook Integration** - Real-time event handling with built-in webhook endpoints
- 🗄️ **Database Logging** - Automatic request logging for debugging and monitoring
- 🔄 **Auto Token Refresh** - Background token refresh to maintain API connectivity

## Requirements

- PHP 8.2 and above.
- Laravel 10 and above.

## Installation

You can install the package via composer:

```bash
composer require laraditz/tiktok
```

## Quick Start

### 1. TikTok Shop App Setup

Before using this package, you need to create a TikTok Shop app:

1. Visit [TikTok Shop Partner Center](https://partner.tiktokshop.com/)
2. Create a new app or use an existing one
3. Note your `App Key` and `App Secret`
4. Configure the redirect URL: `https://your-app-url.com/tiktok/seller/authorized`

### 2. Environment Configuration

Add your TikTok Shop credentials to your `.env` file:

```env
TIKTOK_APP_KEY=your_app_key_here
TIKTOK_APP_SECRET=your_app_secret_here
TIKTOK_SHOP_ID=your_shop_id_here          # Optional: Default shop ID
TIKTOK_SHOP_CODE=your_shop_code_here      # Optional: Default shop code
TIKTOK_SHOP_NAME=your_shop_name_here      # Optional: Default shop name
```

### 3. Publish Migration

You can publish the migration file via this command:

```bash
php artisan vendor:publish --provider="Laraditz\TikTok\TikTokServiceProvider" --tag="migrations"
```

### 4. Run Migration

Run the migration command to create the necessary database tables:

```bash
php artisan migrate
```

This creates tables for shops, access tokens, requests, webhooks, orders, and returns.

### 5. Configuration (Optional)

Publish the configuration file if you need to customize settings:

```bash
php artisan vendor:publish --provider="Laraditz\TikTok\TikTokServiceProvider" --tag="config"
```

### 6. Authorization Flow

To authorize a TikTok shop with your app:

1. **In Partner Center**, go to `App & Service` and select your app. On the right side, you can find `Authorization` section with `Copy authorization link` button. Copy the URL and paste it into your browser address. The URL look like this:

```
 https://services.tiktokshop.com/open/authorize?service_id=720850956892765XXXXX
```

2. **Login using the seller account** that you want to authorized to be use for the app.

3. **TikTok redirects back** to `https://your-app-url.com/tiktok/seller/authorized`
4. **Package automatically handles** the authorization code exchange and token storage
5. **Shop is now ready** for API calls

## Available Methods

Here’s the full list of methods available in this package. Each method uses the same parameters defined in the [TikTok Shop API Documentation](https://partner.tiktokshop.com/docv2/page/6789f6f818828103147a8b05). You don’t need to worry about adding common parameters like `app_key`, `sign`, `timestamp`, or `shop_cipher` because they’ll be automatically included whenever required.

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

| Method              | Description                                                                             | Parameters                                |
| ------------------- | --------------------------------------------------------------------------------------- | ----------------------------------------- |
| `list()`            | Retrieve a list of products that meet the specified conditions.                         | query: `page_size`, `page_token`          |
|                     |                                                                                         | body: `status`, `update_time_ge` and more |
| `get()`             | Retrieve all properties of a product that is in the DRAFT, PENDING, or ACTIVATE status. | params: `product_id`                      |
| `updateInventory()` | Retrieve all properties of a product that is in the DRAFT, PENDING, or ACTIVATE status. | params: `product_id`                      |
|                     |                                                                                         | body: `skus`                              |

### Return Service `return()`

Full parameters refer to [API documentation](https://partner.tiktokshop.com/docv2/page/return-refund-and-cancel-api-overview)

| Method   | Description                                   | Parameters                                |
| -------- | --------------------------------------------- | ----------------------------------------- |
| `list()` | Use this API to retrieve one or more returns. | query: `page_size`, `page_token` and more |
| `get()`  | Use this API to get a list of return records. | params: `return_id`                       |

## Usage Examples

### Basic Usage

```php
use Laraditz\TikTok\Facades\TikTok;

// Using facade (recommended)
$shops = TikTok::seller()->shops();

// Using service container
$seller = app('tiktok')->seller()->shops();
```

### Working with Products

```php
// Get all products
$products = TikTok::product()->list(
    query: [
        'page_size' => 20
    ],
    body: [
        'status' => 'ALL',
        'update_time_ge' => 1758211200  // Unix timestamp
    ]
);

// Get specific product
$product = TikTok::product()->get(
    params: [
        'product_id' => 'your_product_id'
    ]
);

// Update SKU quantity of a product
$updateInventory = TikTok::product()->updateInventory(
    params: [
        'product_id' => 'your_product_id'
    ],
    body: [
        'skus' => [
            [
                'id' => 'your_product_sku_id',
                'inventory' => [
                    [
                        'warehouse_id' => 'your_warehouse_id',
                        'quantity' => 17,
                    ]
                ]
            ]
        ]
    ],
);
```

### Order Management

```php
// Search orders
$orders = TikTok::order()->list(
    query: [
        'page_size' => 50
    ],
    body: [
        'order_status' => 'UNPAID',
        'create_time_ge' => 1758211200,
        'create_time_lt' => 1758297600
    ]
);

// Get order details
$orderDetails = TikTok::order()->detail(
    query: [
        'ids' => 'order_id_1,order_id_2'
    ]
);

// Get order pricing details
$pricing = TikTok::order()->priceDetail(
    params: [
        'order_id' => 'your_order_id'
    ]
);
```

### Return Order

```php
// Get returns
$returns = TikTok::return()->list(
    query: [
        'page_size' => 20
    ],
    body: [
        'create_time_ge' => 1758211200
    ]
);

// Get specific return records
$returnRecords = TikTok::return()->get(
    params: [
        'return_id' => '168129934203XXXX' // A unique identifier for a TikTok Shop return request.
    ]
);
```

### Multi-Shop Support

By default, the package uses `TIKTOK_SHOP_ID` from your `.env` file. For multi-shop applications, specify the shop ID per request:

```php
// Method 1: Using make() with shop_id
$products = TikTok::make(shop_id: '7010123456556XXXXXX')
    ->product()
    ->list(
        query: ['page_size' => 10],
        body: ['status' => 'ACTIVATE']
    );

// Method 2: Using shopId() method
$orders = TikTok::shopId('7010123456556XXXXXX')
    ->order()
    ->list(
        query: ['page_size' => 50],
        body: ['order_status' => 'UNPAID', 'create_time_ge' => 1758211200]
    );

// You also can set shop context and reuse
$tiktok = TikTok::make(shop_id: '7010123456556XXXXXX');
$products = $tiktok->product()->list(/* ... */);
$orders = $tiktok->order()->list(/* ... */);
```

### Error Handling

```php
use Laraditz\TikTok\Exceptions\TikTokAPIError;

try {
    $products = TikTok::product()->list(
        query: ['page_size' => 10],
        body: ['status' => 'ALL']
    );

    // Handle successful response
    $data = $products['data'];

} catch (TikTokAPIError $e) {
    // Handle API errors
    $errorCode = $e->getCode();
    $errorMessage = $e->getMessage();
    $errorData = $e->getData();

    logger()->error('TikTok API Error', [
        'code' => $errorCode,
        'message' => $errorMessage,
        'data' => $errorData
    ]);
}
```

## Events & Webhooks

### Available Events

This package provides events that you can listen to in your application:

| Event                                        | Description                              |
| -------------------------------------------- | ---------------------------------------- |
| `Laraditz\TikTok\Events\WebhookReceived`     | Triggered when TikTok sends webhook data |
| `Laraditz\TikTok\Events\TikTokRequestFailed` | Triggered when API request fails         |

### Creating Event Listeners

Create listeners for these events in your application:

```php
// app/Listeners/TikTokWebhookListener.php
<?php

namespace App\Listeners;

use Laraditz\TikTok\Events\WebhookReceived;

class TikTokWebhookListener
{
    public function handle(WebhookReceived $event)
    {
        $eventType = $event->eventType;
        $data = $event->data;

        match ($eventType) {
            'ORDER_STATUS_CHANGE' => $this->handleOrderStatusChange($eventType, $data),
            'RETURN_STATUS_CHANGE' => $this->handleReturnStatusChange($eventType, $data),
            // Handle other event types
        }
    }

    private function handleOrderStatusChange(string $eventType, array $data)
    {
        // Your order status change logic here
    }

    private function handleReturnStatusChange(string $eventType, array $data)
    {
        // Your return order logic here
    }
}
```

Register the listener in your `EventServiceProvider` (Laravel 10 and below):

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    \Laraditz\TikTok\Events\WebhookReceived::class => [
        \App\Listeners\TikTokWebhookListener::class,
    ],
    \Laraditz\TikTok\Events\TikTokRequestFailed::class => [
        \App\Listeners\TikTokRequestFailedListener::class,
    ],
];
```

### Webhook Configuration

#### 1. Universal Webhook Endpoint

Configure this URL in your TikTok Shop App Management section under `Manage Webhook` to receive all webhook events:

```
https://your-app-url.com/tiktok/webhooks/all
```

#### 2. Event-Specific Webhooks

You can also register individual webhooks for specific events:

```php
// Register webhook for order status changes
TikTok::event()->updateWebhook(
    body: [
        'event_type' => 'ORDER_STATUS_CHANGE',
        'address' => 'https://your-app-url.com/tiktok/webhooks/order-status',
    ]
);

// Register webhook for product updates
TikTok::event()->updateWebhook(
    body: [
        'event_type' => 'PRODUCT_UPDATE',
        'address' => 'https://your-app-url.com/tiktok/webhooks/product-update',
    ]
);
```

#### 3. Managing Webhooks

```php
// List all registered webhooks
$webhooks = TikTok::event()->webhookList();

// Delete a specific webhook
TikTok::event()->deleteWebhook(
    body: [
        'event_type' => 'ORDER_STATUS_CHANGE'
    ]
);
```

Read more about TikTok Webhooks in the [official documentation](https://partner.tiktokshop.com/docv2/page/64f1997e93f5dc028e357341) and [webhook section](https://partner.tiktokshop.com/docv2/page/17-shoppable-content-posting).

## Token Management

### Artisan Commands

The package provides Artisan commands for token management:

```bash
# Refresh access tokens before they expire
php artisan tiktok:refresh-token

# Remove expired tokens from database
php artisan tiktok:flush-expired-token
```

### Automated Token Refresh

Set up automatic token refresh in your `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Refresh tokens daily (tokens expire in 7 days)
    $schedule->command('tiktok:refresh-token')
             ->daily()
             ->withoutOverlapping()
             ->onFailure(function () {
                 // Log failures or send notifications
             });

    // Clean up expired tokens weekly
    $schedule->command('tiktok:flush-expired-token')
             ->weekly();
}
```

### Token Lifecycle

Understanding TikTok token duration:

| Token Type        | Duration  | Notes                         |
| ----------------- | --------- | ----------------------------- |
| **Access Token**  | 7 days    | Used for API calls            |
| **Refresh Token** | ~2 months | Used to refresh access tokens |

**Important:** If tokens expire and refresh fails, sellers must re-authorize your app.

## Testing

### Running Tests

```bash
composer test
```

### Debug Mode

Enable debug logging by listening to the `TikTokRequestFailed` event:

```php
// In your EventServiceProvider
use Laraditz\TikTok\Events\TikTokRequestFailed;

protected $listen = [
    TikTokRequestFailed::class => [
        function (TikTokRequestFailed $event) {
            logger()->error('TikTok API Request Failed', [
                'method' => $event->fqcn . '::' . $event->methodName,
                'query' => $event->query,
                'body' => $event->body,
                'message' => $event->message,
            ]);
        }
    ],
];
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information about recent changes.

## Contributing

We welcome contributions! Please see [CONTRIBUTING](CONTRIBUTING.md) for guidelines on:

- Reporting bugs
- Suggesting enhancements
- Submitting pull requests
- Code style standards

## Security

If you discover any security vulnerabilities, please email [raditzfarhan@gmail.com](mailto:raditzfarhan@gmail.com) instead of using the issue tracker. All security vulnerabilities will be promptly addressed.

## Support

- 📖 [TikTok Shop API Documentation](https://partner.tiktokshop.com/docv2/)
- 🐛 [Issue Tracker](https://github.com/laraditz/tiktok/issues)
- 💬 [Discussions](https://github.com/laraditz/tiktok/discussions)

## Credits

- [Raditz Farhan](https://github.com/laraditz) - Creator and maintainer
- [All Contributors](../../contributors) - Thank you for your contributions!

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
