# Security Policy

## Supported Versions

| Version | Supported          |
|---------|--------------------|
| 3.1.x   | ✅ |
| < 3.1   | ❌ |

## Security Architecture

### NAAN Validation

The plugin uses a server-side validation model to prevent fake-validation attacks:

1. Plugin sends {naan, domain, timestamp} to revistacarnaubais.com.br/ark-telemetry/validate
2. Server validates against n2t.net using trusted code
3. Server compares registered domain with provided domain
4. Server returns {valid: true/false, message: "..."}

Why this is secure:
- Validation code runs on the server, not on the client
- Prevents fake-validation of NAANs
- Uses rate limiting with exponential backoff

### Plugin Identity Verification

The plugin creates a file `identity.txt` in its folder to verify it is actually installed:

- Created automatically during installation
- Contains a random token
- Server verifies the file exists before accepting statistics
- Prevents external actors from sending data on behalf of the journal

### Data Collection (Opt-in)

If enabled by the journal manager, the plugin sends:

{
  "naan": "ark:16081",
  "domain": "revistacarnaubais.com.br",
  "arks_count": 1250,
  "plugin_version": "3.1.0.0"
}

- No personal data: No emails, names, or identifiable information
- No credentials: No tokens, secrets, or persistent auth
- Push model: Journal controls when data is sent (monthly via scheduled task)
- Opt-in: Disabled by default, journal must explicitly enable
- Token-based: Temporary tokens (expire in 5 minutes) for each transmission

### Consent Tracking (LGPD/GDPR)

All consent changes are audited:

- When a journal manager enables/disables telemetry
- Timestamp of the change
- Previous and new values
- Stored in `ark_validations` table

### Security Controls

| Control | Implementation |
|---------|----------------|
| Authentication | OJS session + role validation (Manager/Editor required) |
| CSRF Protection | Token validation on all state-changing operations |
| Rate Limiting | Exponential backoff per IP (server-side) |
| SQL Injection | Prepared statements, exact match only |
| Timing Attacks | hash_equals() for all credential comparisons |
| PostgreSQL Support | Database-agnostic queries |
| SSL/TLS | All external communication encrypted |
| Identity Verification | File-based (identity.txt) |

## Reporting a Vulnerability

Please do NOT report security vulnerabilities via GitHub Issues.

Instead, send an email to:

- Email: m.luryhortencio@gmail.com
- Subject: [ARK Plugin Security] - [Brief Description]

### What to include:

1. Description of the vulnerability
2. Steps to reproduce
3. Potential impact
4. Suggested fix (if any)

### Response Timeline:

| Severity | Initial Response | Fix Release |
|----------|------------------|-------------|
| Critical | 24 hours | 7 days |
| High | 48 hours | 14 days |
| Medium | 72 hours | 30 days |
| Low | 7 days | 60 days |

## Security Best Practices for Users

### Journal Managers

1. Review permissions: Only grant Manager/Editor roles to trusted users
2. Update regularly: Keep the plugin updated to the latest version
3. Review telemetry consent: Check if data sharing is enabled as desired

### Server Administrators

1. Check logs: Monitor for failed validation attempts
2. Database backup: Regular backups of issue_settings and publication_settings
3. Verify identity.txt: Ensure the plugin's identity file is present and accessible

## Security Features Summary

| Feature | Status |
|---------|--------|
| NAAN Server-side Validation | ✅ Implemented |
| Plugin Identity Verification | ✅ Implemented |
| Rate Limiting | ✅ Implemented |
| CSRF Protection | ✅ Implemented |
| OJS Authentication | ✅ Implemented |
| Role-Based Access | ✅ Implemented |
| SQL Injection Prevention | ✅ Implemented |
| Timing Attack Protection | ✅ Implemented |
| PostgreSQL Support | ✅ Implemented |
| SSL/TLS Encryption | ✅ Implemented |
| Data Minimization | ✅ Implemented |
| Opt-in Telemetry | ✅ Implemented |
| Consent Audit | ✅ Implemented |
| Privacy Policy | ✅ Available |

## Third-Party Dependencies

The plugin uses:

- PHP: 7.4+
- OJS: 3.5.0+
- Composer dependencies: None external (uses OJS core)

## Disclosure Policy

Security vulnerabilities will be disclosed:

1. Private disclosure: First to the reporter (with credit)
2. Fix release: Patch released within the timeline above
3. Public disclosure: After fix is released and widely adopted

Last Updated: 2026-07-04
Version: 3.1.0.0