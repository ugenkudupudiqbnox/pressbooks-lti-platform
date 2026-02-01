#!/usr/bin/env bash
set -e

PB_CONTAINER=$(docker ps --filter "name=pressbooks" --format "{{.ID}}")

echo "📦 Installing Pressbooks LTI plugin"

docker exec "$PB_CONTAINER" bash -c "
cd /var/www/html/wp-content/plugins &&
if [ ! -d pressbooks-lti-platform ]; then
  cp -r /workspace/pressbooks-lti-platform/plugin pressbooks-lti-platform
fi
wp plugin activate pressbooks-lti-platform --network
"

echo "✅ Plugin installed & activated"

