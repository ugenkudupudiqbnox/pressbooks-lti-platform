#
# Command to run: make up install enable-lti seed test
#

.PHONY: up down install enable-lti seed test logs reset

LAB_DIR=lti-local-lab

all:
	make up install enable-lti seed test

up:
	@bash scripts/lab-up.sh

install:
	@bash scripts/install-plugin.sh

enable-lti:
	@bash scripts/moodle-register-lti.sh

seed:
	@bash scripts/seed-moodle.sh

test:
	@bash scripts/lti-smoke-test.sh

logs:
	docker compose -f $(LAB_DIR)/docker-compose.yml logs -f

down:
	docker compose -f $(LAB_DIR)/docker-compose.yml down

reset:
	docker compose -f $(LAB_DIR)/docker-compose.yml down -v

