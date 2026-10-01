# Changelog

All notable changes to `laraditz/tiktok` will be documented in this file

## 1.1.6 - 2026-10-01

### Added

- Webhooks with a type ID not in the `EventType` enum are now accepted on `/tiktok/webhooks/all`, stored and dispatched with event type `UNKNOWN` instead of being rejected
- `typeId` property on the `WebhookReceived` event
- New `EventType` cases: `COMBINED_LISTING_CHANGE` (42), `IMAGE_TRANSLATION_COMPLETED` (46), `SKU_STATUS_CHANGE` (50), `GLOBAL_REPLICATION_STATUS_CHANGE` (51), `GLOBAL_LISTING_METHOD_CHANGE` (52), `VIDEO_PRECHECK_RESULT` (55), `SAMPLE_APPLICATION_STATUS_CHANGE` (56), `FBT_MCF_ORDER_STATUS` (58), `SHOPPABLE_VIDEO_PRECHECK_TASKS_RESULT` (59), `PRODUCT_PACKAGE_RECOMMENDED` (62), `ACTIVITY_CHANGE` (63), `AFTERSALES_REQUEST_STATUS_UPDATE` (64), `RMA_STATUS_UPDATE` (65), `APPEAL_COMPLETED` (66), `REFUND_SUCCESS` (67), `INVENTORY_CHANGED` (68), `INVENTORY_CHANGED_BY_SHOP` (71)

### Changed

- **Breaking:** `EventType::INVOICCE_STATUS_CHANGE` renamed to `INVOICE_STATUS_CHANGE` (36)
- **Breaking:** `EventType::FBT_SELLER_ONBOARDING` renamed to `FBT_MERCHANT_ONBOARDING` (22)

### Fixed

- Return status change webhooks (type 12) now update the `tiktok_return_orders` table
- Non-numeric webhook `type` values no longer cause a `TypeError`

## 1.1.5 - 2026-06-15

### Added

- New `FinanceService` (`finance()`) with `statements()`, `payments()`, `withdrawals()`, `unsettledTransactions()`, `transactionsByOrder()`, and `transactionsByStatement()` methods

### Fixed

- Fixed `dropForeign` by name not supported on SQLite in `tiktok_shops` id column migration

## 1.1.4 - 2026-04-22

### Changed

- Fix stale product_id on `tiktok_product_skus` table

## 1.1.3 - 2026-03-25

### Added

- Soft-delete products that do not exist or do not belong to the shop when calling `updateInventory()`

## 1.1.2 - 2026-03-21

### Added

- Added soft-deletes support to `tiktok_products` and `tiktok_product_skus` tables

### Fixed

- Fixed migration bug on `tiktok_event_webhooks` table with foreign key `shop_id`

## 1.1.1 - 2025-10-02

### Changed

- Updated `ORDER_RETURN_STATUS_CHANGE` event type enum to `RETURN_STATUS_CHANGE`

## 1.1.0 - 2025-09-29

### Changed

- **BREAKING**: Updated `tiktok_shops` table structure - ID column now serves as TikTok shop ID
- Improved authorization flow handling
- Enhanced seller authorization view
- Re-authorization Required: Due to these database changes, you will need to re-authorize all your TikTok shops after upgrading to v1.1.0
- Migration Impact: Run the new migrations and ensure your existing shop data is properly migrated before re-authorizing

### Removed

- **BREAKING**: Removed `identifier` column from `tiktok_shops` table as it's no longer used

### Updated

- Updated all model relationships to use new shop ID structure
- Modified base service and authentication classes to work with new shop identification system
- Updated test cases to reflect database schema changes

## 1.0.4 - 2025-09-23

### Fixed

- Bug fix in `addProductSku` method in ProductService class.

## 1.0.3 - 2025-09-21

### Fixed

- Bug fix in `getSignature` method in TikTok class.

## 1.0.2 - 2025-09-20

### Added

- New `updateInventory` method in configuration routes
- Enhanced inventory management capabilities

### Fixed

- Bug fix in `setRouteFromConfig` method in BaseService
- Improved route configuration handling

### Changed

- Updated README.md documentation

## 1.0.1 - 2025-09-20

### Added

- New `tiktok_products` table for storing TikTok product data locally
- New `tiktok_product_skus` table for storing product SKU variations
- Migration publishing functionality - migrations are now published instead of auto-loaded

### Changed

- **BREAKING**: Service provider now publishes migrations instead of auto-loading them
- Users must run `php artisan vendor:publish --provider="Laraditz\TikTok\TikTokServiceProvider" --tag="migrations"` to publish migrations

## 1.0.0 - 2025-09-20

### Added

- Initial release of Laravel TikTok Shop API package
- Complete TikTok Shop API integration with Laravel framework
- Multi-shop support for managing multiple TikTok Shop accounts
- Service-oriented architecture with dedicated services:
  - **ProductService** - Product management and catalog operations
  - **OrderService** - Order processing and management
  - **SellerService** - Seller account and shop information
  - **EventService** - Webhook management and event handling
  - **AuthService** - Authentication and token management
  - **ReturnService** - Return and refund processing
- Automatic API request signing with HMAC-SHA256
- Built-in request/response logging with database storage
- Eloquent models for shops, access tokens, and request logs
- Comprehensive configuration system with environment variables
- Full test suite with 86 tests covering unit, feature, and integration scenarios
- Laravel service provider with auto-discovery
- Facade support for easy access
- Event system for API request monitoring
- Flexible HTTP client with proper error handling
- Automatic access token management and refresh capabilities
- Comprehensive README.md documentation and examples
