#!/bin/bash
#
# URGENT: Fix Production URLs After Migration
#
# This script fixes Moodle and Pressbooks production URLs that were
# accidentally changed to localhost during local migration.
#
# Usage: Run this script on the PRODUCTION server (101.53.135.34)
#

set -e

echo "╔══════════════════════════════════════════════════════════════════════╗"
echo "║         🚨 FIXING PRODUCTION URLs - MOODLE & PRESSBOOKS 🚨          ║"
echo "╚══════════════════════════════════════════════════════════════════════╝"
echo

# Check if we're on production server
if ! docker ps | grep -q mysql; then
    echo "❌ ERROR: No MySQL container found!"
    echo "   Make sure you're running this on the production server."
    exit 1
fi

echo "✅ MySQL container found"
echo

# Fix Moodle
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1️⃣  Fixing Moodle"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Fix Moodle database
echo "Updating Moodle database..."
docker exec mysql mysql -uroot -proot moodle -e "UPDATE mdl_config SET value='https://moodle.lti.qbnox.com' WHERE name='wwwroot';" 2>&1 | grep -v "Using a password"
echo "✓ Database updated"

# Find Moodle container
MOODLE_CONTAINER=$(docker ps --format "{{.Names}}" | grep moodle)
echo "Found Moodle container: $MOODLE_CONTAINER"

# Fix Moodle config.php
echo "Updating Moodle config.php..."
docker exec "$MOODLE_CONTAINER" sed -i "s#http://localhost:8080#https://moodle.lti.qbnox.com#" /var/www/html/config.php
docker exec "$MOODLE_CONTAINER" sed -i "s/\$CFG->sslproxy = false;/\$CFG->sslproxy = true;/" /var/www/html/config.php
docker exec "$MOODLE_CONTAINER" sed -i "s/\$CFG->cookiesecure = false;/\$CFG->cookiesecure = true;/" /var/www/html/config.php
echo "✓ Config.php updated"

# Restart Moodle
echo "Restarting Moodle..."
docker restart "$MOODLE_CONTAINER" > /dev/null
sleep 5
echo "✓ Moodle restarted"

# Verify Moodle
echo "Verifying Moodle..."
MOODLE_CHECK=$(docker exec mysql mysql -uroot -proot -N -e "SELECT value FROM moodle.mdl_config WHERE name='wwwroot';" 2>&1 | grep -v "Using a password")
if [ "$MOODLE_CHECK" = "https://moodle.lti.qbnox.com" ]; then
    echo "✅ Moodle URL: $MOODLE_CHECK"
else
    echo "⚠️  Moodle URL: $MOODLE_CHECK (Expected: https://moodle.lti.qbnox.com)"
fi

echo

# Fix Pressbooks
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "2️⃣  Fixing Pressbooks"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Find Pressbooks container
PB_CONTAINER=$(docker ps --format "{{.Names}}" | grep pressbooks)
echo "Found Pressbooks container: $PB_CONTAINER"

# Fix Pressbooks database - site domains
echo "Updating Pressbooks site domains..."
docker exec mysql mysql -uroot -proot pressbooks -e "UPDATE wp_blogs SET domain='pb.lti.qbnox.com' WHERE domain='localhost';" 2>&1 | grep -v "Using a password"
echo "✓ Site domains updated"

# Fix Pressbooks database - wp_options
echo "Updating Pressbooks wp_options..."
docker exec mysql mysql -uroot -proot pressbooks -e "UPDATE wp_options SET option_value='https://pb.lti.qbnox.com' WHERE option_value='http://localhost:8081';" 2>&1 | grep -v "Using a password"
docker exec mysql mysql -uroot -proot pressbooks -e "UPDATE wp_options SET option_value=REPLACE(option_value, 'http://localhost:8081', 'https://pb.lti.qbnox.com');" 2>&1 | grep -v "Using a password"
docker exec mysql mysql -uroot -proot pressbooks -e "UPDATE wp_options SET option_value=REPLACE(option_value, 'localhost', 'pb.lti.qbnox.com') WHERE option_value LIKE '%localhost%';" 2>&1 | grep -v "Using a password"
echo "✓ wp_options updated"

# Fix LTI platform issuer
echo "Updating LTI platform issuer..."
docker exec mysql mysql -uroot -proot pressbooks -e "UPDATE wp_lti_platforms SET issuer='https://moodle.lti.qbnox.com';" 2>&1 | grep -v "Using a password"
echo "✓ LTI platform issuer updated"

# Restart Pressbooks
echo "Restarting Pressbooks..."
docker restart "$PB_CONTAINER" > /dev/null
sleep 5
echo "✓ Pressbooks restarted"

# Verify Pressbooks
echo "Verifying Pressbooks..."
PB_CHECK=$(docker exec mysql mysql -uroot -proot -N -e "SELECT domain FROM pressbooks.wp_blogs WHERE blog_id=1;" 2>&1 | grep -v "Using a password")
if [ "$PB_CHECK" = "pb.lti.qbnox.com" ]; then
    echo "✅ Pressbooks domain: $PB_CHECK"
else
    echo "⚠️  Pressbooks domain: $PB_CHECK (Expected: pb.lti.qbnox.com)"
fi

LTI_CHECK=$(docker exec mysql mysql -uroot -proot -N -e "SELECT issuer FROM pressbooks.wp_lti_platforms LIMIT 1;" 2>&1 | grep -v "Using a password")
if [ "$LTI_CHECK" = "https://moodle.lti.qbnox.com" ]; then
    echo "✅ LTI issuer: $LTI_CHECK"
else
    echo "⚠️  LTI issuer: $LTI_CHECK (Expected: https://moodle.lti.qbnox.com)"
fi

echo
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "3️⃣  Testing Production URLs"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo

echo "Testing Moodle..."
MOODLE_HTTP=$(curl -sI https://moodle.lti.qbnox.com | head -1)
echo "   $MOODLE_HTTP"
if echo "$MOODLE_HTTP" | grep -q "200\|302"; then
    echo "   ✅ Moodle is accessible"
else
    echo "   ⚠️  Moodle may have issues"
fi

echo
echo "Testing Pressbooks..."
PB_HTTP=$(curl -sI https://pb.lti.qbnox.com | head -1)
echo "   $PB_HTTP"
if echo "$PB_HTTP" | grep -q "200\|302"; then
    echo "   ✅ Pressbooks is accessible"
else
    echo "   ⚠️  Pressbooks may have issues"
fi

echo
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ PRODUCTION FIX COMPLETE!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo
echo "Please test the following URLs in your browser:"
echo "  • Moodle:     https://moodle.lti.qbnox.com"
echo "  • Pressbooks: https://pb.lti.qbnox.com"
echo
echo "If you still see localhost redirects, clear your browser cache."
echo
