#!/usr/bin/env bash
set -e

OUT=ci-artifacts
mkdir -p "$OUT"

echo "📦 Collecting CI artifacts"

# Move into compose directory so docker compose works
cd lti-local-lab

# Resolve container IDs dynamically
MOODLE_CID=$(docker compose ps -q moodle || true)
PRESSBOOKS_CID=$(docker compose ps -q pressbooks || true)

if [ -z "$MOODLE_CID" ] || [ -z "$PRESSBOOKS_CID" ]; then
  echo "❌ Containers not running – skipping artifact collection"
  exit 1
fi

echo "➡ Moodle container: $MOODLE_CID"
echo "➡ Pressbooks container: $PRESSBOOKS_CID"

# Logs
docker logs "$MOODLE_CID" > "../$OUT/moodle.log" || true
docker logs "$PRESSBOOKS_CID" > "../$OUT/pressbooks.log" || true

# Moodle DB dump
docker exec "$MOODLE_CID" bash -c "
mysqldump -uroot -proot moodle > /tmp/moodle.sql
"

docker cp "$MOODLE_CID:/tmp/moodle.sql" "../$OUT/moodle.sql"

echo "✅ CI artifacts collected in $OUT/"
