# Security Policy

**ARK Plugin for OJS** | Version: 3.1.0.0 | Last Updated: 2026-07-04

---

## Supported Versions

| Version | Supported |
|---------|-----------|
| 3.1.x   | ✅ |
| < 3.1   | ❌ |

## Security Architecture

### Authentication & Authorization

| Control | Implementation |
|---------|----------------|
| User Authentication | OJS session validation |
| Role-Based Access | Manager/Editor only for ARK operations |
| CSRF Protection | Token validation on all state changes |
| API Authentication | Temporary tokens (5 min expiry) |

### Data Protection

| Control | Implementation |
|---------|----------------|
| Encryption | SSL/TLS for all external communication |
| Rate Limiting | Exponential backoff per IP |
| SQL Injection | Prepared statements, exact match only |
| Timing Attacks | hash_equals() for all comparisons |
| Database | PostgreSQL and MySQL compatible |

### Telemetry Security

- **Opt-out:** Data is sent by default, user can disable
- **Minimal data:** Only NAAN, ARK count, plugin version
- **No PII:** No emails, names, IPs, or user data
- **Push model:** Journal controls when data is sent
- **Audit trail:** All consent changes are logged

### Plugin Identity Verification

The plugin uses a two-layer verification system to ensure only legitimate journals can send telemetry data:

#### Layer 1: File-based Verification (`identity.txt`)

The plugin automatically creates an `identity.txt` file in the plugin folder during installation. This file serves as proof that the plugin is actually installed on the domain.

- Created automatically when the plugin is activated
- Telemetry server verifies the file exists on the domain before accepting data
- Prevents external actors from sending data on behalf of the journal

#### Layer 2: Private Key Verification

In addition to the identity file, the plugin generates a unique private key during installation.

- A unique key is generated and stored in the OJS database
- The key is registered with the telemetry server during configuration
- Each statistics submission requires the private key for authentication

#### Why Two Layers?

- `identity.txt`: Verifies the plugin exists on the domain
- Private key: Verifies the submission comes from the legitimate journal
- Together, they prevent spoofing and unauthorized access

---

## Reporting a Vulnerability

**DO NOT** report via GitHub Issues.

**Email:** m.luryhortencio@gmail.com \
**Subject:** [ARK Plugin Security] - Brief  Description

### What to Include

1. Description of the vulnerability
2. Steps to reproduce
3. Potential impact
4. Suggested fix (if any)

### Response Timeline

| Severity | Initial Response | Fix Release |
|----------|------------------|-------------|
| Critical | 24 hours | 7 days |
| High | 48 hours | 14 days |
| Medium | 72 hours | 30 days |
| Low | 7 days | 60 days |

---

## Security Best Practices

### For Journal Managers

1. Review permissions: Only grant Manager/Editor roles to trusted users
2. Update regularly: Keep plugin updated to latest version
3. Review telemetry: Check if data sharing is enabled as desired

### For Server Administrators

1. Monitor logs: Check for failed validation attempts
2. Regular backups: Backup issue_settings and publication_settings
3. Verify identity.txt: Ensure plugin's identity file is present

---

## Security Features Summary

| Feature | Status |
|---------|--------|
| OJS Authentication | ✅ |
| Role-Based Access | ✅ |
| CSRF Protection | ✅ |
| Rate Limiting | ✅ |
| SQL Injection Prevention | ✅ |
| Timing Attack Protection | ✅ |
| SSL/TLS Encryption | ✅ |
| Data Minimization | ✅ |
| Opt-out Telemetry | ✅ |
| Consent Audit | ✅ |
| Privacy Policy | ✅ |
| PostgreSQL Support | ✅ |

---

## Disclosure Policy

Security vulnerabilities will be disclosed:

1. **Private disclosure:** First to reporter (with credit)
2. **Fix release:** Patch within timeline above
3. **Public disclosure:** After fix is released and adopted

---

## Third-Party Dependencies

- PHP: 7.4+
- OJS: 3.5.0+
- No external Composer dependencies (uses OJS core)

---

**Contact:** m.luryhortencio@gmail.com \
**GitHub:** https://github.com/lurymorais/ark-plugin