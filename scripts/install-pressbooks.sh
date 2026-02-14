#!/usr/bin/env bash
set -Eeuo pipefail

echo "🚀 Starting Pressbooks Installation"

########################################
# Detect docker compose command
########################################
if command -v docker-compose >/dev/null 2>&1; then
  DC="docker-compose"
else
  DC="docker compose"
fi

########################################
# Defaults (GitHub Actions safe)
########################################
DB_CONTAINER="${DB_CONTAINER:-mysql}"
WP_CONTAINER="${WP_CONTAINER:-wordpress}"

DB_NAME="${DB_NAME:-pressbooks}"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD:-root}"
DB_HOST="${DB_HOST:-mysql}"

WP_HOME="${WP_HOME:-http://localhost:8000}"
WP_SITEURL="${WP_SITEURL:-http://localhost:8000/wp}"
WP_TITLE="${WP_TITLE:-Pressbooks}"
WP_ADMIN_USER="${WP_ADMIN_USER:-admin}"
WP_ADMIN_PASSWORD="${WP_ADMIN_PASSWORD:-admin}"
WP_ADMIN_EMAIL="${WP_ADMIN_EMAIL:-admin@example.com}"

########################################
# Warn if .env missing
########################################
if [ ! -f .env ]; then
  echo "⚠️  Warning: .env file not found, using defaults"
fi

########################################
# Wait for MySQL health
########################################
echo "⏳ Waiting for MySQL to be healthy..."

until $DC ps --format json 2>/dev/null | grep -q "\"Name\":\"${DB_CONTAINER}\""; do
  sleep 2
done

until $DC exec -T "$DB_CONTAINER" mysqladmin ping -h"localhost" --silent; do
  sleep 3
done

echo "✅ MySQL container is healthy"

########################################
# Create .env safely
########################################
echo "📚 Setting up Pressbooks Bedrock"

cat > .env <<EOF
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}
DB_HOST=${DB_HOST}

WP_HOME=${WP_HOME}
WP_SITEURL=${WP_SITEURL}
EOF

echo "✅ .env created"

########################################
# Install WordPress (if not installed)
########################################
if ! $DC exec -T "$WP_CONTAINER" wp core is-installed >/dev/null 2>&1; then
  echo "📦 Installing WordPress..."

  $DC exec -T "$WP_CONTAINER" wp core install \
    --url="${WP_HOME}" \
    --title="${WP_TITLE}" \
    --admin_user="${WP_ADMIN_USER}" \
    --admin_password="${WP_ADMIN_PASSWORD}" \
    --admin_email="${WP_ADMIN_EMAIL}" \
    --skip-email

  echo "✅ WordPress installed"
else
  echo "ℹ️ WordPress already installed"
fi

########################################
# Activate Pressbooks
########################################
echo "🔌 Activating Pressbooks plugin..."

$DC exec -T "$WP_CONTAINER" wp plugin activate pressbooks || true

echo "✅ Pressbooks activated"

########################################
# Ensure multisite (Pressbooks requirement)
########################################
if ! $DC exec -T "$WP_CONTAINER" wp core is-installed --network >/dev/null 2>&1; then
  echo "🌐 Enabling Multisite..."

  $DC exec -T "$WP_CONTAINER" wp core multisite-convert || true
fi

echo "✅ Multisite ready"

########################################
# Final status
########################################
echo "🎉 Pressbooks setup completed successfully"
