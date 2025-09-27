# 🚀 CMMS Deployment Checklist

## ✅ Pre-Deployment Checklist

### 🔧 Environment Configuration

- [x] External database connection verified (103.152.5.77:3309)
- [x] Docker Compose configuration validated
- [x] Environment variables properly set
- [x] Production vs Development configs separated

### 🔒 Security Checklist

- [x] Nginx security headers configured
- [x] PHP-FPM security settings applied
- [x] File permissions properly set
- [x] Sensitive files excluded from Docker context
- [ ] SSL/TLS certificates configured (if needed)
- [ ] Firewall rules configured
- [ ] Database credentials secured

### 📦 Docker Configuration

- [x] Dockerfile optimized for production
- [x] Multi-stage build implemented
- [x] Health checks configured
- [x] Volume strategy optimized
- [x] Resource limits set (if needed)

### 🗄️ Database Setup

- [x] External MySQL database accessible
- [x] Database credentials configured
- [x] Connection string validated
- [ ] Database migrations ready
- [ ] Seed data prepared (if needed)

### 📁 File System

- [x] Storage volumes configured
- [x] Public storage symlink ready
- [x] Log directories created
- [x] Cache directories prepared

### 🔄 Application Setup

- [x] Laravel application key generated
- [x] Composer dependencies installed
- [x] NPM assets built
- [x] Queue workers configured
- [x] Scheduled tasks configured

## 🚨 Critical Issues to Address

### ⚠️ Security Concerns

1. **APP_DEBUG=true** - Should be false in production
2. **LOG_LEVEL=debug** - Should be error/warning in production
3. **Missing SSL/TLS** - Consider HTTPS for production
4. **Database credentials** - Ensure strong passwords

### 🔧 Configuration Issues

1. **APP_URL** - Update to production domain
2. **Mail configuration** - Configure SMTP for production
3. **File upload limits** - Review upload_max_filesize
4. **Session configuration** - Review session security

## 🚀 Deployment Steps

### 1. Pre-Deployment

```bash
# Test Docker build locally
docker-compose build --no-cache

# Validate configuration
docker-compose config

# Test startup
docker-compose up -d
```

### 2. Database Setup

```bash
# Run migrations
docker-compose exec app php artisan migrate

# Seed initial data (if needed)
docker-compose exec app php artisan db:seed

# Create storage symlink
docker-compose exec app php artisan storage:link
```

### 3. Production Deployment

```bash
# Use production compose file
docker-compose -f docker-compose.prod.yml up -d

# Verify services
docker-compose -f docker-compose.prod.yml ps

# Check logs
docker-compose -f docker-compose.prod.yml logs -f
```

### 4. Post-Deployment Verification

```bash
# Test health endpoint
curl http://your-domain.com/health

# Test database connection
docker-compose exec app php artisan tinker
# DB::connection()->getPdo();

# Check file permissions
docker-compose exec app ls -la storage/
```

## 📊 Monitoring

### Health Checks

- Application: `GET /health`
- Redis: `redis-cli ping`
- Database: Laravel connection test

### Logs to Monitor

- Application logs: `storage/logs/laravel.log`
- Nginx logs: `/var/log/nginx/`
- PHP-FPM logs: Supervisor logs
- Docker logs: `docker-compose logs`

## 🔧 Troubleshooting

### Common Issues

1. **Database connection failed** - Check credentials and network
2. **Permission denied** - Verify file ownership
3. **Health check failed** - Check application startup
4. **Volume mount issues** - Verify volume configuration

### Emergency Commands

```bash
# Restart all services
docker-compose restart

# View real-time logs
docker-compose logs -f app

# Access container shell
docker-compose exec app bash

# Check resource usage
docker stats
```

## 📋 Post-Deployment Tasks

- [ ] Set up monitoring alerts
- [ ] Configure backup strategy
- [ ] Set up log rotation
- [ ] Configure SSL certificates
- [ ] Update DNS records
- [ ] Test all functionality
- [ ] Document access credentials
- [ ] Set up maintenance schedule
