#!/usr/bin/env bash
# One-click LTI smoke test (basic reachability + endpoints)

set -e

MOODLE_URL=${MOODLE_URL:-https://moodle.local}
PRESSBOOKS_URL=${PRESSBOOKS_URL:-https://pressbooks.local}

echo "🔍 Checking Moodle..."
curl -k -s -o /dev/null -w "%{http_code}" $MOODLE_URL | grep -q 200 && echo "✅ Moodle OK"

echo "🔍 Checking Pressbooks..."
curl -k -s -o /dev/null -w "%{http_code}" $PRESSBOOKS_URL | grep -q 200 && echo "✅ Pressbooks OK"

echo "🔍 Checking LTI endpoints..."
for ep in login launch keyset; do
  curl -k -s -o /dev/null -w "%{http_code}" $PRESSBOOKS_URL/wp-json/pb-lti/v1/$ep | grep -qE "200|405" && echo "✅ $ep endpoint OK"
done

echo "🎉 Smoke test completed successfully"
