# Security & Compliance Memo
## Pressbooks LTI Platform (LTI 1.3 + Advantage)

### Overview
This system implements a secure LTI 1.3 + LTI Advantage integration for Pressbooks,
aligned with 1EdTech specifications and higher-education security expectations.

### Security Controls
- OAuth2 + OpenID Connect (LTI 1.3)
- RS256 JWT validation with key rotation
- Encrypted client_secret storage (AES-256-GCM)
- Token caching with expiry handling
- Nonce-based replay protection
- Deployment ID validation
- Strict AGS scope enforcement

### Compliance Alignment
- 1EdTech LTI 1.3 Core
- LTI Deep Linking 2.0
- LTI Assignment & Grade Services
- OWASP ASVS (relevant sections)
- ISO 27001 control alignment (logical access, cryptography, audit logging)

### Audit & Monitoring
- Centralized audit log with admin viewer
- Logged events include launches, failures, score posts, and security violations
- Logs retained per institutional policy

### Risk Statement
Residual risk is limited to:
- LMS-side compromise (out of scope)
- Browser-based client compromise (out of scope)

All other risks are mitigated or auditable.

### Conclusion
The platform is suitable for production deployment in a public university environment
and meets security and compliance expectations for LMS integrations.
