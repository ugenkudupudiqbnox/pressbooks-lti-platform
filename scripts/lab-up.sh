#!/usr/bin/env bash
set -e

echo "🚀 Starting local LTI lab"

cd lti-local-lab
docker compose up -d

echo "⏳ Waiting for services (90s)"
sleep 90

echo "✅ Moodle + Pressbooks containers running"

