SHELL := /bin/bash

# Start/stop stack
up:
	docker compose up -d --build

down:
	docker compose down

logs:
	docker compose logs -f --tail=100

bash:
	docker compose exec app bash

# 🚀 Reset Laravel application (fresh install)
reset-laravel:
	# 1. Stop stack
	docker compose down
	# 2. Wipe existing Laravel code (incl. hidden files)
	sudo rm -rf src/* src/.* 2>/dev/null || true
	# 3. Create new Laravel app in src/
	docker compose run --rm app bash -lc "composer create-project laravel/laravel ."
	# 4. Copy default .env if missing
	@if [ ! -f src/.env ]; then cp src/.env.example src/.env; fi
	# 5. Start stack again
	docker compose up -d
	# 6. Generate app key and run migrations
	docker compose exec app php artisan key:generate
	docker compose exec app php artisan migrate --force --no-interaction
