
#!/usr/bin/env bash
set -e
echo "Setting up Docker-based LTI lab (Pressbooks + Moodle)"
docker compose up -d
sleep 60
bash scripts/register-lti-tool.sh
