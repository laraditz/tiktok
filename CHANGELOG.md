# Changelog

All notable changes to `laraditz/tiktok` will be documented in this file

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
