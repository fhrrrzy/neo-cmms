# Docker Setup for CMMS Laravel Application

This document provides instructions for setting up and running the CMMS application using Docker.

## Prerequisites

- Docker Desktop or Docker Engine
- Docker Compose

## Services Included

- **Laravel App**: PHP 8.2 with Apache
- **MySQL 8.0**: Database server
- **Redis**: Cache and session storage
- **Node.js**: For Vite development server
- **Mailhog**: Email testing
- **phpMyAdmin**: Database management

## Quick Start

1. **Clone and navigate to the project directory**

    ```bash
    cd /path/to/new-cmms
    ```

2. **Copy environment file**

    ```bash
    cp .env.example .env
    ```

3. **Update .env file with Docker configuration**
   Update the following variables in your `.env` file:

    ```env
    APP_URL=http://localhost:8000
    DB_HOST=mysql
    DB_DATABASE=cmms
    DB_USERNAME=cmms_user
    DB_PASSWORD=cmms_password
    REDIS_HOST=redis
    MAIL_HOST=mailhog
    MAIL_PORT=1025
    ```

4. **Build and start containers**

    ```bash
    docker-compose up -d --build
    ```

5. **Install dependencies and setup Laravel**

    ```bash
    # Install PHP dependencies
    docker-compose exec app composer install

    # Install Node.js dependencies
    docker-compose exec node npm install

    # Generate application key
    docker-compose exec app php artisan key:generate

    # Run database migrations
    docker-compose exec app php artisan migrate

    # Create storage link
    docker-compose exec app php artisan storage:link
    ```

## Access Points

- **Laravel Application**: http://localhost:8000
- **Filament Admin Panel**: http://localhost:8000/admin
- **phpMyAdmin**: http://localhost:8080
- **Mailhog**: http://localhost:8025
- **Vite Dev Server**: http://localhost:5173

## Default Database Credentials

- **Database**: cmms
- **Username**: cmms_user
- **Password**: cmms_password
- **Root Password**: root_password

## Volumes

The following volumes are configured for persistent data:

- `mysql-data`: MySQL database files
- `redis-data`: Redis data
- `app-storage`: Laravel storage directory
- `app-vendor`: Composer vendor directory
- `app-node_modules`: Node.js modules

## Development Commands

### Run specific services

```bash
# Start only the app and database
docker-compose up app mysql redis

# Start with Node.js development server
docker-compose up app mysql redis node
```

### Execute commands in containers

```bash
# Run Artisan commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan tinker

# Run Composer commands
docker-compose exec app composer install
docker-compose exec app composer update

# Run NPM commands
docker-compose exec node npm install
docker-compose exec node npm run dev

# Access container shell
docker-compose exec app bash
docker-compose exec mysql bash
```

### View logs

```bash
# View all logs
docker-compose logs

# View specific service logs
docker-compose logs app
docker-compose logs mysql
docker-compose logs redis
```

## Production Considerations

For production deployment, consider:

1. **Update environment variables**:
    - Set `APP_ENV=production`
    - Set `APP_DEBUG=false`
    - Use strong database passwords
    - Configure proper mail settings

2. **Security**:
    - Remove development services (mailhog, phpmyadmin)
    - Use secrets management for sensitive data
    - Enable SSL/TLS

3. **Performance**:
    - Use production-optimized PHP configuration
    - Enable OPcache
    - Use Redis for sessions and cache
    - Configure proper MySQL settings

## Troubleshooting

### Container won't start

```bash
# Check container status
docker-compose ps

# View logs for errors
docker-compose logs [service-name]

# Rebuild containers
docker-compose down
docker-compose up --build
```

### Permission issues

```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data storage
docker-compose exec app chmod -R 775 storage
```

### Database connection issues

```bash
# Check if MySQL is running
docker-compose exec mysql mysql -u root -p

# Test connection from app container
docker-compose exec app php artisan tinker
```

## Cleanup

To remove all containers, networks, and volumes:

```bash
# Stop and remove containers
docker-compose down

# Remove everything including volumes (WARNING: This will delete all data)
docker-compose down -v

# Remove images
docker-compose down --rmi all
```
