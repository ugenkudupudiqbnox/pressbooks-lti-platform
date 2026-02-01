#!/usr/bin/env bash
set -e

MOODLE_CONTAINER=$(docker ps --filter "name=moodle" --format "{{.ID}}")

echo "🌱 Seeding Moodle"

docker exec "$MOODLE_CONTAINER" bash -c "
php admin/tool/generator/cli/maketestcourse.php --size=S
php admin/tool/generator/cli/maketestusers.php --count=20
"

echo "✅ Users & course created"

