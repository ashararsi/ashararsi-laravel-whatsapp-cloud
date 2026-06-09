# Changelog

All notable changes to `ashararsi/laravel-whatsapp-cloud` will be documented in this file.

## [2.0.0-beta.1] - 2026-06-09

### Added
- WhatsApp CRM platform: contact tags, notes (admin UI), conversation inbox with reply
- Interactive Meta buttons and lists API (`sendButtons`, `sendList`)
- Media download service for incoming Meta attachments
- OpenAI integration: AI auto-reply engine and Whisper audio transcription
- Auto-reply rules engine with keyword, first-message, and AI modes
- AI agent workflows with configurable steps
- Broadcast campaigns module with admin UI, queue option, and `whatsapp:campaigns:run`
- Scheduled messages with `whatsapp:scheduled:send` command
- Template sync command `whatsapp:templates:sync`
- Analytics dashboard with 7-day message volume table
- Conversation full-text search across contacts and messages
- Typed event system (campaign, AI, media, workflow, reply events)
- Incoming message pipeline listener (media, transcription, auto-reply, workflows)

### Fixed
- Replaced invalid variadic event constructors that caused PHP 8.3/8.4 fatal errors
- Admin reply, campaign dispatch, media download, and AI pipelines no longer crash on event dispatch
- Notes and tags admin UI on contact detail page
- Graceful failure handling for OpenAI transcription and AI reply failures

### Note
- `whatsapp_tenants` schema columns exist for future use; multi-tenant isolation is **not** implemented
- Filament integration is **not** included; use the built-in Bootstrap admin panel or publish views

[2.0.0-beta.1]: https://github.com/ashararsi/ashararsi-laravel-whatsapp-cloud/releases/tag/v2.0.0-beta.1

## [1.2.0] - 2026-06-09

### Added
- Per-account Meta `app_secret` for webhook signature verification (fallback to global secret)
- Webhook idempotency: duplicate Meta message and status deliveries are ignored
- Incoming message logging to `whatsapp_messages` with `direction` (`incoming` / `outgoing`)
- Migration: `direction`, `from`, unique `whatsapp_message_id` on `whatsapp_messages`
- Admin conversation reply form with optional queue dispatch and error handling
- Twilio inbound webhook: `POST /whatsapp/twilio/webhook` (text, media, location)
- Twilio status callbacks: `POST /whatsapp/twilio/status` (`queued`, `sent`, `delivered`, `failed`, `undelivered`)
- `TwilioSignatureValidator` for `X-Twilio-Signature` verification
- `php artisan whatsapp:doctor` health check command (PASS / WARNING / ERROR report)
- PHPStan (level 5), Laravel Pint, and expanded GitHub Actions quality workflow
- 15+ new tests covering security, idempotency, Twilio, doctor, and inbox replies

### Changed
- `WebhookHandler` resolves account before signature validation
- `MessageLogger::logIncoming()` stores inbound messages with idempotent `firstOrCreate`
- README: Meta/Twilio setup, webhooks, inbox replies, doctor command, security guidance

### Security
- Meta webhooks can require per-account HMAC signatures
- Twilio webhooks validate request signatures by default

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

[1.2.0]: https://github.com/ashararsi/ashararsi-laravel-whatsapp-cloud/releases/tag/v1.2.0
[1.1.0]: https://github.com/ashararsi/ashararsi-laravel-whatsapp-cloud/releases/tag/v1.1.0
[1.0.0]: https://github.com/ashararsi/ashararsi-laravel-whatsapp-cloud/releases/tag/v1.0.0
