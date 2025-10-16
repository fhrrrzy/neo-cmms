# CMMS Docker Setup

This document provides complete instructions for setting up and running the CMMS application using Docker.

## 🏗️ Architecture

The Docker setup includes:

- **app**: PHP-FPM application container
- **nginx**: Web server with optimized configuration
- **queue-worker**: Supervisor-managed queue workers
- **scheduler**: Laravel scheduler service
- **redis**: Redis cache and queue backend

## 📋 Prerequisites

- Docker and Docker Compose installed
- External MySQL database accessible
- Configured `.env` file

## 🚀 Quick Start

### Development Environment

```bash
# Make setup script executable
chmod +x docker/scripts/setup.sh

# Run setup script
./docker/scripts/setup.sh
```

### Production Environment

```bash
# Make deploy script executable
chmod +x docker/scripts/deploy.sh

# Deploy to production
./docker/scripts/deploy.sh
```

## ⚙️ Configuration

### Environment Variables

Copy `env.docker.example` to `.env` and configure:

```bash
cp env.docker.example .env
```

Key configurations:

- `DB_HOST`: Your external MySQL host
- `DB_DATABASE`: Database name
- `DB_USERNAME`: Database username
- `DB_PASSWORD`: Database password
- `IMS_TOKEN`: Your IMS API token

### External MySQL Connection

The setup uses an external MySQL database. Ensure your database is accessible from the Docker containers and configure the connection in `.env`.

## 🐳 Docker Commands

### Development

```bash
# Start development environment
docker-compose -f docker-compose.dev.yml up -d

# View logs
docker-compose -f docker-compose.dev.yml logs -f

# Stop containers
docker-compose -f docker-compose.dev.yml down

# Access app container
docker-compose -f docker-compose.dev.yml exec app bash
```

### Production

```bash
# Start production environment
docker-compose -f docker-compose.prod.yml up -d

# Scale queue workers
docker-compose -f docker-compose.prod.yml up -d --scale queue-worker=4

# View logs
docker-compose -f docker-compose.prod.yml logs -f

# Stop containers
docker-compose -f docker-compose.prod.yml down
```

## 🔧 Services

### Queue Workers

- **Supervisor-managed**: Automatic restart on failure
- **Multiple processes**: Configurable number of workers
- **Queue priorities**: `high` and `default` queues
- **Resource limits**: Memory and timeout protection

### Scheduler

- **Laravel scheduler**: Runs every minute via cron
- **Background service**: Separate container for reliability
- **Logging**: Comprehensive scheduler logs

### Nginx

- **Optimized configuration**: Gzip, caching, security headers
- **PHP-FPM integration**: Fast CGI processing
- **SSL ready**: HTTPS configuration available
- **File upload support**: 100MB limit

## 📊 Monitoring

### Monitor Script

```bash
# Make monitor script executable
chmod +x docker/scripts/monitor.sh

# Run monitoring dashboard
./docker/scripts/monitor.sh
```

### Manual Monitoring

```bash
# Check container status
docker-compose -f docker-compose.prod.yml ps

# View resource usage
docker stats

# Check queue status
docker-compose -f docker-compose.prod.yml exec app php artisan queue:monitor

# View logs
docker-compose -f docker-compose.prod.yml logs -f
```

## 🔄 Maintenance

### Backup

```bash
# Make backup script executable
chmod +x docker/scripts/backup.sh

# Create backup
./docker/scripts/backup.sh
```

### Updates

```bash
# Pull latest code
git pull

# Rebuild containers
docker-compose -f docker-compose.prod.yml build --no-cache

# Restart services
docker-compose -f docker-compose.prod.yml restart
```

### Scaling

```bash
# Scale queue workers
docker-compose -f docker-compose.prod.yml up -d --scale queue-worker=6

# Scale app containers (if needed)
docker-compose -f docker-compose.prod.yml up -d --scale app=2
```

## 🚨 Troubleshooting

### Common Issues

1. **MySQL Connection Failed**
    - Check database credentials in `.env`
    - Ensure MySQL is accessible from Docker network
    - Verify firewall settings

2. **Queue Workers Not Processing**
    - Check supervisor logs: `docker-compose logs queue-worker`
    - Verify Redis connection
    - Check queue configuration

3. **Scheduler Not Running**
    - Check scheduler logs: `docker-compose logs scheduler`
    - Verify cron configuration
    - Check Laravel schedule configuration

4. **Storage Link Issues**
    - Run: `docker-compose exec app php artisan storage:link`
    - Check storage permissions
    - Verify volume mounts

### Log Locations

- **Application logs**: `storage/logs/laravel.log`
- **Queue worker logs**: `storage/logs/worker.log`
- **Scheduler logs**: `storage/logs/scheduler.log`
- **Nginx logs**: Available via `docker-compose logs nginx`

## 📁 File Structure

```
docker/
├── nginx/
│   └── default.conf          # Nginx configuration
├── supervisor/
│   ├── laravel-worker.conf   # Queue worker configuration
│   └── laravel-scheduler.conf # Scheduler configuration
├── cron/
│   └── laravel-schedule      # Cron configuration
└── scripts/
    ├── setup.sh              # Development setup
    ├── deploy.sh             # Production deployment
    ├── backup.sh             # Backup script
    ├── monitor.sh            # Monitoring dashboard
    └── entrypoint.sh         # Container entrypoint
```

## 🔐 Security

- **Security headers**: XSS protection, content type sniffing prevention
- **File access control**: Sensitive files blocked
- **SSL ready**: HTTPS configuration available
- **Environment isolation**: Production and development separation

## 📈 Performance

- **Gzip compression**: Enabled for static assets
- **Asset caching**: Long-term caching for static files
- **PHP-FPM optimization**: Fast CGI processing
- **Redis caching**: Session and cache storage
- **Queue optimization**: Multiple worker processes

## 🆘 Support

For issues or questions:

1. Check the logs first
2. Run the monitoring script
3. Verify configuration
4. Check Docker and Docker Compose versions
