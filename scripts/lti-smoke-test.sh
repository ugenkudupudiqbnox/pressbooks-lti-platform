#!/usr/bin/env bash
set -e

echo "🔍 Smoke testing endpoints"

curl -k https://moodle.local >/dev/null
curl -k https://pressbooks.local >/dev/null
curl -k https://pressbooks.local/wp-json/pb-lti/v1/keyset >/dev/null

echo "🎉 Smoke test passed"

