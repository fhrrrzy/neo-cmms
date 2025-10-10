# Docker Setup Guide

This guide will help you set up the Laravel CMMS application using Docker from a fresh repository clone.

## Prerequisites

- Docker and Docker Compose installed
- Git installed

## Quick Start

### 1. Clone the Repository

```bash
git clone <your-repo-url>
cd new-cmms
```

### 2. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
docker run --rm -v $(pwd):/app -w /app php:8.4-cli php artisan key:generate
```

### 3. Start the Application

```bash
# Build and start all services
docker-compose up --build -d

# Check status
docker-compose ps
```

### 4. Database Setup (if needed)

```bash
# Run migrations
docker-compose exec app php artisan migrate

# Seed database (optional)
docker-compose exec app php artisan db:seed
```

## Services

The application consists of the following services:

- **app**: Laravel PHP-FPM application
- **nginx**: Web server
- **worker**: Queue workers (3 instances)
- **scheduler**: Laravel task scheduler
- **redis**: Cache and session storage

## Volumes

The application uses named volumes for data persistence:

- `storage_data`: Laravel storage directory
- `cache_data`: Laravel cache directory
- `public_data`: Public assets
- `redis_data`: Redis data

## Commands

### View Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f worker
docker-compose logs -f scheduler
```

### Execute Commands

```bash
# Artisan commands
docker-compose exec app php artisan <command>

# Composer commands
docker-compose exec app composer <command>

# Access shell
docker-compose exec app sh
```

### Stop Services

```bash
# Stop all services
docker-compose down

# Stop and remove volumes (WARNING: This will delete all data)
docker-compose down -v
```

## Production Deployment

For production deployment:

1. Update `.env` with production values
2. Ensure proper SSL certificates in `docker/nginx/ssl/`
3. Configure your reverse proxy (Traefik/Nginx) to point to the `nginx` service
4. Set up database backups for the `redis_data` volume

## Troubleshooting

### Permission Issues

The application automatically handles permissions via the init script. If you encounter permission issues:

```bash
# Check container logs
docker-compose logs app

# Rebuild containers
docker-compose down
docker-compose up --build -d
```

### Port Conflicts

If you encounter port conflicts, ensure no other services are using ports 80, 443, or 6379.

### Volume Issues

If volumes are corrupted:

```bash
# Remove all volumes and recreate
docker-compose down -v
docker volume prune -f
docker-compose up -d
```

## Development

For development, you can mount your local code:

```yaml
# In docker-compose.yml, change the app service volumes:
volumes:
    - .:/var/www/html
    - storage_data:/var/www/html/storage
    - cache_data:/var/www/html/bootstrap/cache
```

This will allow live code changes without rebuilding the container.
