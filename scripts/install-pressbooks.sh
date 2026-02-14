#!/usr/bin/env bash
set -e

# Load environment configuration
source "$(dirname "$0")/load-env.sh"

PB_CONTAINER=$(docker ps --filter "name=pressbooks" --format "{{.ID}}")

if [ -z "$PB_CONTAINER" ]; then
  echo "❌ Pressbooks container not running"
  exit 1
fi

# Wait for MySQL container to be healthy
echo "⏳ Waiting for MySQL to be healthy..."
TIMEOUT=120
ELAPSED=0
until docker ps --filter "name=mysql" --format "{{.Status}}" | grep -q "healthy"; do
  if [ $ELAPSED -ge $TIMEOUT ]; then
    echo "❌ Timeout waiting for MySQL container to be healthy"
    echo "📊 MySQL container status:"
    docker ps --filter "name=mysql" --format "table {{.Names}}\t{{.Status}}"
    exit 1
  fi
  echo "  ⏳ MySQL not healthy yet... (${ELAPSED}s/${TIMEOUT}s)"
  sleep 5
  ELAPSED=$((ELAPSED + 5))
done
echo "✅ MySQL container is healthy"

echo "📚 Setting up Pressbooks Bedrock"

docker exec "$PB_CONTAINER" bash -c "
set -e

# Install required packages
echo '📥 Installing required packages...'
apt-get update -qq >/dev/null 2>&1
apt-get install -y -qq default-mysql-client unzip curl git >/dev/null 2>&1
echo '✅ Required packages installed'

# Install WP-CLI
if ! command -v wp &> /dev/null; then
  echo '📥 Installing WP-CLI...'
  curl -sS -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
  chmod +x wp-cli.phar
  mv wp-cli.phar /usr/local/bin/wp
  echo '✅ WP-CLI installed'
fi

# Install Composer
if ! command -v composer &> /dev/null; then
  echo '📥 Installing Composer...'
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --quiet
  echo '✅ Composer installed'
fi

# Database configuration
DB_NAME=\${WORDPRESS_DB_NAME:-wordpress}
DB_USER=\${WORDPRESS_DB_USER:-root}
DB_PASS=\${WORDPRESS_DB_PASSWORD:-root}
DB_HOST=\${WORDPRESS_DB_HOST:-mysql}

echo \"📊 Database config: \$DB_USER@\$DB_HOST/\$DB_NAME\"

# Wait for MySQL to be ready
echo '🔧 Checking MySQL connection...'
TIMEOUT=120
ELAPSED=0
until mysql -h\$DB_HOST -u\$DB_USER -p\$DB_PASS --skip-ssl -e 'SELECT 1' >/dev/null 2>&1; do
  if [ \$ELAPSED -ge \$TIMEOUT ]; then
    echo '❌ Timeout waiting for MySQL'
    exit 1
  fi
  echo \"  ⏳ Waiting for MySQL... (\${ELAPSED}s/\${TIMEOUT}s)\"
  sleep 5
  ELAPSED=\$((ELAPSED + 5))
done
echo '✅ MySQL is ready'

# Create database if needed
if ! mysql -h\$DB_HOST -u\$DB_USER -p\$DB_PASS --skip-ssl -e \"USE \$DB_NAME\" >/dev/null 2>&1; then
  echo \"📦 Creating database '\$DB_NAME'...\"
  mysql -h\$DB_HOST -u\$DB_USER -p\$DB_PASS --skip-ssl -e \"CREATE DATABASE IF NOT EXISTS \$DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\"
  echo '✅ Database created'
else
  echo '✅ Database exists'
fi

# Check if Bedrock is already set up
if [ -f /var/www/html/web/wp-config.php ]; then
  echo '✅ Bedrock already set up'
else
  echo '🔧 Setting up Bedrock from scratch...'

  # Backup existing WordPress if present
  if [ -d /var/www/html.bak ]; then
    rm -rf /var/www/html.bak
  fi
  mv /var/www/html /var/www/html.bak 2>/dev/null || true

  # Create Pressbooks Bedrock project
  echo '📦 Creating Pressbooks Bedrock project (this may take 3-5 minutes)...'
  cd /var/www
  composer create-project pressbooks/pressbooksoss-bedrock html --no-interaction --quiet

  cd /var/www/html

  # Create .env file
  echo '⚙️  Configuring .env...'
  cat > .env << 'ENVEOF'
DB_NAME=\${DB_NAME}
DB_USER=\${DB_USER}
DB_PASSWORD=\${DB_PASS}
DB_HOST=\${DB_HOST}

WP_ENV=development
WP_HOME=${PRESSBOOKS_URL}
WP_SITEURL=\${WP_HOME}/wp

# Generate salts at: https://roots.io/salts.html
AUTH_KEY='generateme'
SECURE_AUTH_KEY='generateme'
LOGGED_IN_KEY='generateme'
NONCE_KEY='generateme'
AUTH_SALT='generateme'
SECURE_AUTH_SALT='generateme'
LOGGED_IN_SALT='generateme'
NONCE_SALT='generateme'

# Multisite
WP_ALLOW_MULTISITE=true
MULTISITE=true
SUBDOMAIN_INSTALL=false
DOMAIN_CURRENT_SITE=${PRESSBOOKS_DOMAIN}
PATH_CURRENT_SITE=/
ENVEOF

  # Update Apache DocumentRoot
  echo '🔧 Configuring Apache DocumentRoot for Bedrock...'
  sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/web|g' /etc/apache2/sites-available/000-default.conf
  service apache2 reload 2>/dev/null || true

  # Set permissions
  echo '🔧 Setting permissions...'
  chown -R www-data:www-data /var/www/html
  find /var/www/html -type d -exec chmod 755 {} \\;
  find /var/www/html -type f -exec chmod 644 {} \\;

  echo '✅ Bedrock setup complete'
fi

# Install WordPress if not installed
echo '🔧 Checking WordPress installation...'
cd /var/www/html/web/wp
if ! wp core is-installed --allow-root 2>/dev/null; then
  echo '🚀 Installing WordPress multisite...'
  wp core multisite-install \\
    --url=\"${PRESSBOOKS_URL}\" \\
    --title='Pressbooks LTI Platform' \\
    --admin_user=admin \\
    --admin_password=admin123 \\
    --admin_email=admin@example.com \\
    --skip-email \\
    --allow-root
  echo '✅ WordPress multisite installed'
else
  echo '✅ WordPress already installed'
fi

# Activate Pressbooks
echo '📦 Activating Pressbooks...'
if wp plugin is-installed pressbooks --allow-root 2>/dev/null; then
  wp plugin activate pressbooks --network --allow-root 2>/dev/null || true
  echo '✅ Pressbooks activated'
else
  echo '⚠️  Pressbooks plugin not found in Bedrock installation'
fi

# Install H5P plugin
echo '📦 Installing H5P plugin...'
cd /var/www/html/web/wp
if ! wp plugin is-installed h5p --allow-root 2>/dev/null; then
  wp plugin install h5p --activate-network --allow-root
  echo '✅ H5P plugin installed'
else
  echo '✅ H5P already installed'
fi

# Configure .htaccess for Bedrock multisite
echo '🔧 Configuring .htaccess for Bedrock multisite...'
cat > /var/www/html/web/.htaccess << 'HTACCESS_EOF'
# BEGIN WordPress Multisite
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\\.php\$ - [L]

# add a trailing slash to /wp-admin
RewriteRule ^([_0-9a-zA-Z-]+/)?wp-admin\$ \$1wp-admin/ [R=301,L]

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(wp-(content|admin|includes).*) wp/\$2 [L]
RewriteRule ^([_0-9a-zA-Z-]+/)?(.*.php)\$ wp/\$2 [L]
RewriteRule . index.php [L]
</IfModule>
# END WordPress Multisite
HTACCESS_EOF

echo '✅ .htaccess configured'

# Set final permissions
chown -R www-data:www-data /var/www/html
echo '✅ Pressbooks Bedrock installation complete'
"
