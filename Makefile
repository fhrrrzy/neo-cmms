.PHONY: help build up down restart logs shell clean rebuild

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

build: ## Build Docker images
	docker-compose build

up: ## Start containers in background
	docker-compose up -d

down: ## Stop and remove containers
	docker-compose down

restart: ## Restart all containers
	docker-compose restart

logs: ## Follow logs from all containers
	docker-compose logs -f

logs-app: ## Follow logs from app container only
	docker-compose logs -f app

shell: ## Open shell in app container
	docker exec -it cmms-app sh

shell-root: ## Open shell as root in app container
	docker exec -it -u root cmms-app sh

clean: ## Remove containers, volumes, and images
	docker-compose down -v
	docker system prune -f

rebuild: clean build up ## Clean rebuild everything

status: ## Show container status
	docker-compose ps

health: ## Check health status
	@curl -s http://localhost:8081/health || echo "Service not responding"

test-build: ## Build without cache for testing
	docker-compose build --no-cache

db-fresh: ## Fresh database migration (DESTRUCTIVE!)
	docker exec cmms-app php artisan migrate:fresh --seed

db-migrate: ## Run database migrations
	docker exec cmms-app php artisan migrate

cache-clear: ## Clear all Laravel caches
	docker exec cmms-app php artisan cache:clear
	docker exec cmms-app php artisan config:clear
	docker exec cmms-app php artisan route:clear
	docker exec cmms-app php artisan view:clear

optimize: ## Optimize Laravel
	docker exec cmms-app php artisan optimize
	docker exec cmms-app php artisan filament:optimize

composer-install: ## Install composer dependencies
	docker exec cmms-app composer install --no-dev --optimize-autoloader

worker-logs: ## Follow worker logs
	docker exec cmms-app tail -f storage/logs/worker.log

scheduler-logs: ## Follow scheduler logs
	docker exec cmms-app tail -f storage/logs/scheduler.log

nginx-test: ## Test nginx configuration
	docker exec cmms-app nginx -t

php-info: ## Show PHP info
	docker exec cmms-app php -i

stats: ## Show resource usage
	docker stats --no-stream



