### 4. CHANGELOG.md (Atualizado)

# Changelog

All notable changes to the ARK Plugin for OJS will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.1.0.0] - 2026-07-04

### Security (Critical)

#### Removed (Vulnerabilities Addressed)
- REMOVED: plugin_ark_token - No longer stores persistent authentication credentials
- REMOVED: ark_admin_secret - Removed token regeneration mechanism
- REMOVED: /ark-api/telemetry public pull endpoint (was insecure)
- REMOVED: /ark-api/regenerate endpoint (no longer needed)
- REMOVED: recovery system (no tokens to recover)
- REMOVED: Email collection from telemetry (no personal data)
- REMOVED: "Complete" telemetry level (only data needed is NAAN + count + version)

#### Fixed (Security Vulnerabilities)
- FIXED: save_ajax.php - Now requires OJS authentication, CSRF token, and proper roles
- FIXED: ArkPageHandler::authorize() - No longer returns true unconditionally
- FIXED: Token comparisons now use hash_equals() instead of !==
- FIXED: Added PostgreSQL support (database-agnostic DSN)
- FIXED: Removed Access-Control-Allow-Origin: * from sensitive endpoints
- ADDED: CSRF validation on all state-changing operations
- ADDED: Role-based access control (Editor/Manager only for ARK saving)
- ADDED: Rate limiting with exponential backoff on public endpoints

### Added
- Server-side NAAN validation: Plugin validates NAAN via remote server (prevents fake-validation)
- Opt-in telemetry: Journal manager must explicitly enable data sharing (disabled by default)
- Simplified data collection: Only {naan, domain, arks_count, plugin_version}
- Push model: Plugin sends data via OJS scheduled task (no pull from server)
- Privacy Policy: Comprehensive privacy documentation (English, Portuguese, Spanish)
- Security Policy: Vulnerability reporting and disclosure process (SECURITY.md)
- Plugin identity verification: identity.txt file for domain ownership proof
- Consent audit: All telemetry consent changes are logged with timestamp

### Changed
- Version: 2.1.0.0 -> 3.1.0.0 (major version increment due to breaking changes)
- Telemetry: Changed from pull to push model
- Data retention: 24 months, automatic deletion
- Validation: Now server-side only (not local)
- Consent: Opt-in instead of opt-out
- Telemetry data: Reduced to only NAAN, ARK count, and plugin version

### Removed
- classes/handler/ARKRecoveryHandler.inc.php (no longer needed)
- All recovery-related UI elements
- Telemetry level selection (Basic/Complete)
- Email collection functionality
- Token generation and storage
- Pull endpoints

### Documentation
- ADDED: PRIVACY_POLICY.md (English)
- ADDED: PRIVACY_POLICY.pt.md (Portuguese)
- ADDED: PRIVACY_POLICY.es.md (Spanish)
- ADDED: SECURITY.md (Vulnerability reporting)
- ADDED: CHANGELOG.md (This file)
- UPDATED: README.md with accurate telemetry description

## [2.1.0.0] - 2026-06-13

### Added
- Issue ARK generation support
- Enhanced duplicate detection across articles and issues
- Frontend display for issue ARKs
- Brazilian Portuguese, Spanish, Ukrainian translations

### Fixed
- Issue resolver redirection
- ARK generation for issues

## [2.0.0.0] - 2026-06-01

### Added
- Article ARK generation
- n2t.net integration
- Basic resolver
- ERC metadata support
- Custom prefix configuration
- NAAN ownership verification

## [1.0.0.0] - 2026-05-01

### Added
- Initial release
- Basic ARK generation for articles
- Resolver functionality
- Plugin configuration form

---

### Database Changes

No database schema changes required for existing installations.
The plugin uses standard OJS tables (publication_settings, issue_settings, journal_settings).

---

## Security Advisories

### 2026-07-04: Security Release 3.1.0.0

Addressed critical security vulnerabilities:
- Fixed unauthenticated save_ajax.php endpoint
- Removed token regeneration mechanism with permanent access
- Fixed SQL injection in resolver (partial-suffix matching)
- Added plugin identity verification
- Implemented opt-in telemetry with consent audit