#!/usr/bin/env bash
set -e

cd lti-local-lab
docker compose up -d

echo "⏳ Waiting for services to become healthy..."

SERVICES=(mysql moodle pressbooks)

for svc in "${SERVICES[@]}"; do
  echo "➡ Waiting for $svc"
  until [ "$(docker inspect -f '{{.State.Health.Status}}' $svc)" = "healthy" ]; do
    sleep 3
  done
  echo "✅ $svc is healthy"
done

echo "🚀 Local LTI lab is ready"

