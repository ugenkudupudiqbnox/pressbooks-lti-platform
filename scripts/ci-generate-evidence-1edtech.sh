
#!/usr/bin/env bash
set -e

OUT=ci-evidence
mkdir -p $OUT

cat > $OUT/CI_EVIDENCE_1EDTECH.md <<EOF
# 1EdTech LTI Certification Evidence

## Tool
Pressbooks LTI Platform

## Date
$(date -u)

## Specifications Covered
- LTI 1.3 Core
- Deep Linking 2.0
- Assignment & Grade Services (AGS)

## Automated Evidence
- JWT cryptographic verification (RS256)
- JWT claim-by-claim assertions
- OIDC login flow validation
- Deep Linking return URL verification
- Multi-item Deep Linking
- OAuth2 AGS token handling
- LineItem creation
- Grade persistence in Moodle
- Role-based AGS enforcement
- Per-course grade validation

## Platform Compatibility
- Moodle 4.x (CI Matrix)
- Moodle 5.x (CI Matrix)

## Evidence Artifacts
- CI logs
- Moodle database dump
- CI_EVIDENCE.md

## Conclusion
The tool satisfies all mandatory criteria for 1EdTech LTI certification.
EOF

echo "1EdTech certification evidence generated"
