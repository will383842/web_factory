# WebFactory Makefile
# All targets run inside the Docker stack to keep dev parity with prod.
# On Windows, use `make.cmd <target>` from PowerShell/cmd, or `make <target>` from Git Bash/WSL.

DC      := docker compose
EXEC    := $(DC) exec -T wf-app
EXEC_IT := $(DC) exec wf-app
RUN     := $(DC) run --rm wf-app

.PHONY: help setup up down restart build logs ps shell tinker \
        composer-install npm-install \
        migrate migrate-fresh seed \
        test test-back test-front test-arch test-e2e \
        lint lint-php lint-js fix \
        horizon scheduler reverb \
        clean fresh

help:
	@echo "WebFactory — common targets:"
	@echo "  make setup              one-shot bootstrap (build + up + install + migrate + seed)"
	@echo "  make up / down          start / stop the stack"
	@echo "  make restart            restart all services"
	@echo "  make ps / logs          status / tail logs"
	@echo "  make shell / tinker     interactive shell / artisan tinker"
	@echo ""
	@echo "  make migrate            run pending migrations"
	@echo "  make migrate-fresh      drop DB and re-run migrations (DESTRUCTIVE)"
	@echo "  make seed               run database seeders"
	@echo "  make fresh              migrate-fresh + seed"
	@echo ""
	@echo "  make test               run pest + vitest"
	@echo "  make test-back          pest only"
	@echo "  make test-front         vitest only"
	@echo "  make test-arch          architectural rules (Pest)"
	@echo "  make test-e2e           Playwright"
	@echo ""
	@echo "  make lint               pint + phpstan + eslint + prettier --check"
	@echo "  make fix                pint --fix + prettier --write"
	@echo ""
	@echo "  make horizon / scheduler / reverb   tail logs of side-cars"
	@echo "  make clean              docker compose down -v (DESTROYS volumes)"

setup: build up composer-install npm-install
	@$(EXEC) sh -c '[ -f .env ] || cp .env.example .env'
	@$(EXEC) php artisan key:generate --force
	@$(EXEC) php artisan migrate --force
	@$(EXEC) php artisan db:seed --force
	@$(EXEC) npm run build
	@echo ""
	@echo "+--------------------------------------------------+"
	@echo "|  WebFactory ready.                               |"
	@echo "|  http://localhost/up        → 200                |"
	@echo "|  http://localhost/admin     → Filament login     |"
	@echo "+--------------------------------------------------+"

up:
	$(DC) up -d

down:
	$(DC) down

restart:
	$(DC) restart

build:
	$(DC) build --pull

logs:
	$(DC) logs -f --tail=200

ps:
	$(DC) ps

shell:
	$(EXEC_IT) sh

tinker:
	$(EXEC_IT) php artisan tinker

composer-install:
	$(EXEC) composer install --no-progress --prefer-dist

npm-install:
	$(EXEC) npm ci || $(EXEC) npm install

migrate:
	$(EXEC) php artisan migrate --force

migrate-fresh:
	$(EXEC) php artisan migrate:fresh --force

seed:
	$(EXEC) php artisan db:seed --force

fresh: migrate-fresh seed

test: test-back test-front

test-back:
	$(EXEC) ./vendor/bin/pest

test-front:
	$(EXEC) npx vitest run

test-arch:
	$(EXEC) ./vendor/bin/pest --testsuite=Arch

test-e2e:
	$(EXEC) npx playwright test

lint: lint-php lint-js

lint-php:
	$(EXEC) ./vendor/bin/pint --test
	$(EXEC) ./vendor/bin/phpstan analyse --memory-limit=1G

lint-js:
	$(EXEC) npx eslint resources/js tests/js --max-warnings=0
	$(EXEC) npx prettier --check "resources/**/*.{ts,vue,css}"

fix:
	$(EXEC) ./vendor/bin/pint
	$(EXEC) npx prettier --write "resources/**/*.{ts,vue,css}"

horizon:
	$(DC) logs -f wf-horizon

scheduler:
	$(DC) logs -f wf-scheduler

reverb:
	$(DC) logs -f wf-reverb

clean:
	@echo "WARNING: this destroys all data volumes (DB, Redis, Meili, MinIO)."
	@echo "Press Ctrl+C within 5s to abort."
	@sleep 5
	$(DC) down -v
