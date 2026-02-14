#!/usr/bin/env bash
set -e

echo "=== Setting up Moodle Cron ==="

# Check if Moodle container is running
if ! docker ps --format '{{.Names}}' | grep -q "^moodle$"; then
    echo "❌ Error: Moodle container is not running"
    exit 1
fi

# Test Moodle cron manually first
echo "Testing Moodle cron..."
docker exec moodle php /var/www/html/admin/cli/cron.php > /dev/null 2>&1 && echo "✅ Moodle cron executable works" || {
    echo "❌ Error: Moodle cron failed to execute"
    exit 1
}

# Create log directory if it doesn't exist
mkdir -p /var/log

# Save current crontab
crontab -l 2>/dev/null > /tmp/current_cron || touch /tmp/current_cron

# Check if Moodle cron already exists
if grep -q "docker exec moodle php /var/www/html/admin/cli/cron.php" /tmp/current_cron; then
    echo "ℹ️  Moodle cron job already exists in crontab"
else
    echo "Adding Moodle cron job to crontab..."

    # Add Moodle cron entry (runs every 5 minutes)
    cat >> /tmp/current_cron << 'EOF'

# Moodle cron - runs every 5 minutes
*/5 * * * * docker exec moodle php /var/www/html/admin/cli/cron.php >> /var/log/moodle-cron.log 2>&1
EOF

    # Install the new crontab
    crontab /tmp/current_cron
    echo "✅ Moodle cron job added to crontab"
fi

# Clean up temporary file
rm -f /tmp/current_cron

echo ""
echo "✅ Moodle cron setup complete!"
echo ""
echo "Cron will run every 5 minutes automatically."
echo "You can check logs with:"
echo "  tail -f /var/log/moodle-cron.log"
echo ""
echo "To manually trigger cron now:"
echo "  docker exec moodle php /var/www/html/admin/cli/cron.php"
