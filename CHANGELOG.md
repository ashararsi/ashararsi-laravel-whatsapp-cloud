# Changelog

All notable changes to `vendor/laravel-whatsapp-cloud` will be documented in this file.

## [1.2.0] - 2026-06-09

### Added
- Conversation platform: contacts, conversations, conversation messages
- `ConversationService` with automatic webhook + outgoing recording
- Admin dashboard, contacts, and conversations UI with search
- Incoming/outgoing message timeline on conversation detail page
- Tests for conversation service, webhook storage, admin UI

## [1.1.0] - 2026-06-09

### Added
- Provider-based architecture with `WhatsAppProviderInterface`
- `MetaProvider` and `TwilioProvider` implementations
- `ProviderFactory::make($account)` for provider resolution
- Account fields: `provider`, `app_secret`, `twilio_sid`, `twilio_token`, `twilio_whatsapp_number`
- Admin UI provider selector with dynamic credential fields
- Tests: `MetaProviderTest`, `TwilioProviderTest`, `ProviderFactoryTest`

### Changed
- `WhatsAppManager` now delegates to providers (API remains unchanged)
- Twilio message IDs (`sid`) supported in `whatsapp_message_id`
- Account cache stores IDs only (fixes serialization bug)

## [1.0.0] - 2026-06-09

### Added
- Multi-account WhatsApp Cloud API manager with fluent facade API
- Message types: text, template, image, document, audio, video, location
- Webhook verification and event dispatching (`MessageReceived`, `MessageDelivered`, `MessageRead`)
- X-Hub-Signature-256 webhook signature validation
- Admin panel with Bootstrap UI for account CRUD and test messaging
- Queue support via `SendWhatsAppMessageJob`
- Laravel notification channel (`WhatsAppChannel`)
- Artisan commands: `whatsapp:install`, `whatsapp:test`
- Account resolver with optional caching
- `whatsapp_message_id` tracking for delivery/read status updates
- Authorization middleware and gate-based admin protection
- Encrypted access token storage
- GitHub Actions CI workflows
- Comprehensive test suite

### Security
- Hide access tokens from model serialization
- Sanitize admin error responses (no token leakage)
- Webhook spoofing protection via HMAC signature validation
- Fixed Meta webhook verification query params (`hub.mode` notation)

### Changed
- Improved database indexes and unique constraints
- Default template language code set to `en_US` per Meta conventions

[1.0.0]: https://github.com/vendor/laravel-whatsapp-cloud/releases/tag/v1.0.0
