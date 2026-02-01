# Pressbooks + Moodle LTI Advantage Test Checklist

This checklist validates **LTI 1.3 + LTI Advantage** integration between
**Moodle** and **Pressbooks** on a single machine or staging/production.

---

## 0. Environment Sanity Checks
- HTTPS enabled on both Moodle and Pressbooks
- Stable hostnames (not localhost)
- Time synchronized (NTP)

---

## 1. LTI Tool Registration
### Moodle
- Tool type: LTI 1.3
- Login URL, Redirect URI, JWKS URL configured
- Deployment ID generated

### Pressbooks
- Platform registered (issuer, client_id, URLs)
- Deployment ID registered
- Client secret stored securely

---

## 2. Core LTI Launch
- Instructor launch → editor role
- Student launch → subscriber role
- Automatic login works

---

## 3. Security Validation
- Invalid issuer rejected
- Invalid client_id rejected
- Invalid deployment_id rejected
- Invalid aud rejected

---

## 4. Replay Protection
- Refresh launch blocked
- Token reuse blocked

---

## 5. Deep Linking
- Instructor selects content
- Activity launches correct chapter

---

## 6. AGS
- OAuth2 token fetched & cached
- Score posted
- Grade visible in Moodle

---

## 7. Scope Enforcement
- Missing scope rejected
- Correct scope allowed

---

## 8. Audit Logging
- Launch success logged
- Launch failure logged
- AGS events logged

---

## 9. Upgrade Safety
- Restart services
- Launch still works

---

## 10. Acceptance
System is production-ready when all above pass.
