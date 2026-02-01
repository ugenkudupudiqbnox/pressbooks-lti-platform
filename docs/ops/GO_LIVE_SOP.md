# Go-Live SOP – Pressbooks LTI Platform

## 1. Pre-Go-Live Checklist
- Pressbooks upgraded and stable
- Plugin version >= v0.8.0 installed network-wide
- HTTPS enabled
- Database backup completed
- Moodle sandbox available

## 2. Configuration Steps
1. Register LMS platform (issuer, client_id, URLs)
2. Store client_secret via Network Admin → LTI Client Secrets
3. Register deployment IDs
4. Configure allowed AGS scopes
5. (Optional) Create LineItems

## 3. Validation
- Test LTI launch (Instructor, Student)
- Test Deep Linking flow
- Test AGS score posting
- Verify audit logs

## 4. Go-Live
- Enable production LMS deployment
- Monitor audit logs for first 72 hours
- Freeze configuration changes

## 5. Rollback Plan
- Disable plugin network-wide
- Revert DNS / LMS tool configuration
- Restore DB backup

## 6. Post-Go-Live
- Weekly audit log review
- Monthly secret rotation
- Quarterly security review
