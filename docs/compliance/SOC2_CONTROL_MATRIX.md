# SOC 2 Control Matrix
## Pressbooks LTI Platform

| SOC 2 Criterion | Control Description | Evidence |
|-----------------|--------------------|----------|
| CC6.1 | Logical access restricted to authorized users | LTI role mapping, LMS auth |
| CC6.2 | Authentication mechanisms | LTI 1.3 OIDC, JWT validation |
| CC6.6 | Least privilege enforcement | AGS scope enforcement |
| CC7.2 | Monitoring for security events | Audit log viewer |
| CC7.3 | Incident response | SOP, audit logs |
| CC8.1 | Change management | Git history, tagged releases |
| A1.2 | System availability | Token caching, fail-closed design |
| C1.1 | Confidential data protection | Encrypted secrets, no plaintext storage |
| PI1.1 | Processing integrity | Deployment ID & audience validation |
| P1.1 | Privacy controls | Minimal PII, LMS-sourced identities |
