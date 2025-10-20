# New CMMS — Equipment Monitoring & Maintenance

The modern way to monitor plant equipment and streamline maintenance. Track running hours, filter by region/plant/station, and drill into rich equipment details — all in a fast, mobile‑first web app.

## What you can do

- Real‑time equipment monitoring: sortable table with search, pagination, and saved filters.
- Powerful filtering: date range, region, plant, and station with quick apply/reset.
- Equipment drill‑down: detailed profile, location context, specs, and recent running times.
- Visual analytics: dual‑axis running time charts with responsive legends.
- Maintenance insights: work orders and material views tied to each equipment.
- Easy sharing: instant QR code to open a specific equipment page.

## Built for teams

- Mobile‑first UI with smooth transitions and accessible controls.
- Snappy navigation via Inertia pages and cached user preferences.
- Consistent design system built on reusable UI components.

## Tech stack

- Frontend: Vue 3 (script setup, JS only), Inertia, shadcn‑vue components, Highcharts.
- Backend: Laravel API endpoints for monitoring, equipment, regions, and plants.
- State & utilities: Pinia stores for date ranges and filters, Axios for data.

## Value in one line

“See the health of every machine, act on what matters, and keep production running.”

## Primary call to action

- Start monitoring your equipment → View the live table and drill down into any asset.

## Secondary call to action

- Explore a single equipment → Scan or click a QR to jump directly to details.

## Audience

- Plant managers, maintenance supervisors, and reliability engineers who need real‑time visibility and fast decisions.

## Notes

- Pages are implemented in JS (no TypeScript in Vue files).
- UI and layouts follow a mobile‑first approach.

# CMMS - Computerized Maintenance Management System

Laravel-based maintenance management system running on Docker.

## Prerequisites

- Docker
- Docker Compose
- pnpm (for frontend assets)

## Quick Start

### 1. Setup Environment

```bash
# Ensure .env file exists with proper configuration
# The docker-compose.yml reads from .env file
# Required variables:
# - DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
# - APP_ENV, APP_DEBUG
# - REVERB_HOST, REVERB_PORT
```

### 2. Start Services

**Note**: Frontend assets are built automatically inside Docker (multi-stage build)

```bash
docker-compose up -d
```

### 3. Run Migrations & Seed

```bash
docker exec cmms-app php artisan migrate:fresh --seed --force
```

### 4. Create Admin User

```bash
docker exec -it cmms-app php artisan make:filament-user
```

## Access

- **Application**: http://localhost:8988
- **Admin Panel**: http://localhost:8988/admin
- **WebSocket**: ws://localhost:8989

## Common Commands

### View Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f worker
```

### Restart Services

```bash
docker-compose restart
```

### Stop Services

```bash
docker-compose down
```

### Rebuild After Code Changes

```bash
# Rebuild Docker images (includes frontend build)
docker-compose build

# Restart containers
docker-compose up -d
```

### Check Status

```bash
# Quick status
docker-compose ps

# Detailed status
./status.sh
```

### Run Artisan Commands

```bash
docker exec cmms-app php artisan [command]
```

### Access Container Shell

```bash
docker exec -it cmms-app sh
```

## Services

- **app**: Main application (Nginx + PHP-FPM + Supervisor)
    - Ports: 8988 (HTTP), 8989 (WebSocket)
    - Services: Laravel app, scheduler, queue workers, Reverb
- **worker**: Background job processors (3 replicas)
    - 2 queue workers per container
- **redis**: Cache, sessions, and queue backend

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    CMMS Container                       │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌──────────┐  Port 80    ┌─────────────┐               │
│  │  Nginx   │◄────────────┤  PHP-FPM    │               │
│  └──────────┘             └─────────────┘               │
│       │                          ▲                      │
│       │ Proxy                    │                      │
│       ▼                    ┌─────────────┐              │
│  Port 8080  ──────────────►│   Reverb    │ Port 6001    │
│  (WebSocket)                │ (Internal)  │             │
│                             └─────────────┘             │
│                                   │                     │
│                             ┌─────────────┐             │
│                             │ Supervisor  │             │
│                             │   - Queue   │             │
│                             │   - Cron    │             │
│                             │   - Reverb  │             │
│                             └─────────────┘             │
└─────────────────────────────────────────────────────────┘
         │                          │
    Port 8988 (HTTP)           Port 8989 (WS)
```

## Database

- **Type**: MySQL 8.4.6
- **Host**: 103.197.188.18:6969
- **Database**: cmms
- **Credentials**: cmms / cmms

## Environment

Key environment variables are set in `docker-compose.yml`:

- `APP_ENV=local`
- `APP_DEBUG=true`
- `DB_CONNECTION=mysql`
- `CACHE_DRIVER=redis`
- `SESSION_DRIVER=redis`
- `QUEUE_CONNECTION=redis`

Additional variables can be set in `.env` file.

## Troubleshooting

### Container won't start

```bash
docker-compose down
docker-compose up -d
docker-compose logs -f
```

### Database connection error

Check if database is accessible:

```bash
docker exec cmms-app php artisan db:show
```

### Queue not processing

Check worker logs:

```bash
docker logs new-cmms_worker_1
```

### Clear caches

```bash
docker exec cmms-app php artisan config:clear
docker exec cmms-app php artisan cache:clear
docker exec cmms-app php artisan view:clear
```

### Rebuild everything from scratch

```bash
docker-compose down -v
pnpm run build
docker-compose build --no-cache
docker-compose up -d
docker exec cmms-app php artisan migrate:fresh --seed --force
```

## Development

### Frontend Development

```bash
# Watch mode
pnpm run dev

# Build for production
pnpm run build
```

### Backend Development

Code changes will be reflected immediately (mounted as volumes).
For dependency changes, rebuild:

```bash
docker-compose build app worker
docker-compose up -d
```

## Production Deployment

1. Set proper environment variables
2. Disable debug mode: `APP_DEBUG=false`
3. Set `APP_ENV=production`
4. Use proper database credentials
5. Build optimized frontend assets
6. Run migrations without seed

```bash
pnpm run build
docker-compose -f docker-compose.prod.yml up -d
docker exec cmms-app php artisan migrate --force
```
