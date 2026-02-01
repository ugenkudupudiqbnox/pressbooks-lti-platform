#!/usr/bin/env bash
set -e

PB_CONTAINER=$(docker ps --filter "name=pressbooks" --format "{{.ID}}")

echo "📘 Seeding Pressbooks with test book"

docker exec "$PB_CONTAINER" bash -c "
set -e
cd /var/www/html

# Create book site if none exists
if ! wp site list --allow-root | grep -q '/test-book'; then
  wp site create \
    --slug=test-book \
    --title='LTI Test Book' \
    --email=admin@example.com \
    --allow-root
fi

# Switch to book context
BOOK_URL=\$(wp site list --allow-root --field=url | grep test-book)

wp --url=\$BOOK_URL post create \
  --post_type=chapter \
  --post_title='Chapter 1 – Introduction' \
  --post_status=publish \
  --allow-root

wp --url=\$BOOK_URL post create \
  --post_type=chapter \
  --post_title='Chapter 2 – LTI Integration' \
  --post_status=publish \
  --allow-root

echo '✅ Pressbooks book & chapters created'
"

