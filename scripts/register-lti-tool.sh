
#!/usr/bin/env bash
set -e
echo "Auto-registering LTI 1.3 tool in Moodle"

MOODLE_CONTAINER=$(docker ps --filter "ancestor=bitnami/moodle" --format "{{.ID}}")

docker exec "$MOODLE_CONTAINER" bash -c "
php admin/tool/lti/cli/create_tool.php   --name='Pressbooks Local'   --baseurl='https://pressbooks.local'   --initiate_login_url='https://pressbooks.local/wp-json/pb-lti/v1/login'   --redirect_uri='https://pressbooks.local/wp-json/pb-lti/v1/launch'   --jwks_url='https://pressbooks.local/wp-json/pb-lti/v1/keyset'
"
