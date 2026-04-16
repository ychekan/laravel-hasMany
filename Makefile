.PHONY: up down build restart shell logs migrate fresh test lint fix

# ─── Docker ──────────────────────────────────────────────────────────────────
up:          ## Start all services in background
	docker compose up -d

down:        ## Stop all services
	docker compose down

build:       ## Rebuild images (no cache)
	docker compose build --no-cache

restart:     ## Restart a service: make restart s=worker
	docker compose restart $(s)

logs:        ## Tail logs (all or specific: make logs s=worker)
	docker compose logs -f $(s)

# ─── App shell ───────────────────────────────────────────────────────────────
shell:       ## Open bash in app container
	docker compose exec app bash

artisan:     ## Run artisan command: make artisan c="route:list"
	docker compose exec app php artisan $(c)

# ─── Database ─────────────────────────────────────────────────────────────────
migrate:     ## Run pending migrations
	docker compose exec app php artisan migrate --force

fresh:       ## Drop all tables and re-run migrations
	docker compose exec app php artisan migrate:fresh --force

seed:        ## Run database seeders
	docker compose exec app php artisan db:seed --force

# ─── Code quality ────────────────────────────────────────────────────────────
lint:        ## Run Laravel Pint in dry-run mode
	docker compose exec app ./vendor/bin/pint --test

fix:         ## Fix code style with Laravel Pint
	docker compose exec app ./vendor/bin/pint

test:        ## Run PHPUnit tests
	docker compose exec app php artisan test

# ─── Helpers ─────────────────────────────────────────────────────────────────
.DEFAULT_GOAL := help
help:        ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

