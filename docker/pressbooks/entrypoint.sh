#!/usr/bin/env bash
set -e

APP_ROOT="/var/www/pressbooks"
WP_PATH="${APP_ROOT}/web/wp"

# Wait for DB
until mysqladmin ping -h"$DB_HOST" --silent; do
  echo "Waiting for MySQL..."
  sleep 3
done

cd "$APP_ROOT"

# Initialize .env if missing
if [ ! -f .env ]; then
  echo "Initializing .env"
  wp dotenv init --allow-root || true
  wp dotenv salts generate --allow-root || true

  wp dotenv set DB_NAME "$DB_NAME" --allow-root
  wp dotenv set DB_USER "$DB_USER" --allow-root
  wp dotenv set DB_PASSWORD "$DB_PASSWORD" --allow-root
  wp dotenv set DB_HOST "$DB_HOST" --allow-root

  wp dotenv set WP_HOME "$WP_HOME" --allow-root
  wp dotenv set WP_SITEURL "\${WP_HOME}/wp" --allow-root
  wp dotenv set MULTISITE "true" --allow-root
  wp dotenv set SUBDOMAIN_INSTALL "false" --allow-root
  wp dotenv set DOMAIN_CURRENT_SITE "$DOMAIN_CURRENT_SITE" --allow-root
fi

# Install multisite if needed
if ! wp core is-installed --path="$WP_PATH" --allow-root; then
  echo "Installing WordPress Multisite"
  wp core multisite-install \
    --path="$WP_PATH" \
    --url="$WP_HOME" \
    --title="Pressbooks Network" \
    --admin_user="$WP_ADMIN_USER" \
    --admin_password="$WP_ADMIN_PASSWORD" \
    --admin_email="$WP_ADMIN_EMAIL" \
    --skip-email \
    --allow-root
fi

# Network activate Pressbooks
wp plugin activate pressbooks --network --path="$WP_PATH" --allow-root || true

exec "$@"
