#!/bin/bash

# CMMS Docker Status Check Script

set -e

echo "╔════════════════════════════════════════════════════════════╗"
echo "║           CMMS Docker Status Dashboard                     ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}✗ Docker is not running${NC}"
    exit 1
fi

echo -e "${BLUE}━━━ Container Status ━━━${NC}"
docker-compose ps
echo ""

echo -e "${BLUE}━━━ Service Health ━━━${NC}"
# Check main app
if docker exec cmms-app php artisan --version > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} Main Application: Running"
else
    echo -e "${RED}✗${NC} Main Application: Not responding"
fi

# Check database
if docker exec cmms-app php artisan db:show > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} Database: Connected"
else
    echo -e "${YELLOW}⚠${NC} Database: Connection issue"
fi

# Check Redis
if docker exec cmms-app redis-cli -h redis ping > /dev/null 2>&1; then
    echo -e "${GREEN}✓${NC} Redis: Connected"
else
    echo -e "${YELLOW}⚠${NC} Redis: Connection issue"
fi

echo ""

echo -e "${BLUE}━━━ Running Processes ━━━${NC}"
docker exec cmms-app ps aux | head -1
docker exec cmms-app ps aux | grep -E "(supervisor|nginx|php-fpm|artisan)" | grep -v grep | head -10
echo ""

echo -e "${BLUE}━━━ Supervisor Services ━━━${NC}"
echo "Process                  PID     Status"
echo "────────────────────────────────────────"

# Scheduler
SCHED_PID=$(docker exec cmms-app ps aux | grep "schedule:work" | grep -v grep | awk '{print $2}' | head -1)
if [ -n "$SCHED_PID" ]; then
    echo -e "${GREEN}✓${NC} Laravel Scheduler     ${SCHED_PID}     Running"
else
    echo -e "${RED}✗${NC} Laravel Scheduler     -       Stopped"
fi

# Queue Workers
WORKER_COUNT=$(docker exec cmms-app ps aux | grep "queue:work" | grep -v grep | wc -l)
if [ "$WORKER_COUNT" -ge 1 ]; then
    echo -e "${GREEN}✓${NC} Queue Workers (${WORKER_COUNT})     -       Running"
else
    echo -e "${RED}✗${NC} Queue Workers         -       Stopped"
fi

# Reverb
REVERB_PID=$(docker exec cmms-app ps aux | grep "reverb:start" | grep -v grep | awk '{print $2}' | head -1)
if [ -n "$REVERB_PID" ]; then
    echo -e "${GREEN}✓${NC} Reverb WebSocket     ${REVERB_PID}     Running"
else
    echo -e "${YELLOW}⚠${NC} Reverb WebSocket     -       Stopped"
fi

echo ""

echo -e "${BLUE}━━━ PHP Extensions ━━━${NC}"
docker exec cmms-app php -m | grep -E "(pdo_mysql|redis|opcache|pcntl|gd)" | while read ext; do
    echo -e "${GREEN}✓${NC} $ext"
done
echo ""

echo -e "${BLUE}━━━ Application URLs ━━━${NC}"
echo -e "HTTP:      ${GREEN}http://localhost:8988${NC}"
echo -e "Admin:     ${GREEN}http://localhost:8988/admin${NC}"
echo -e "WebSocket: ${GREEN}ws://localhost:8989${NC}"
echo ""

echo -e "${BLUE}━━━ Quick Health Check ━━━${NC}"
# Test HTTP
if curl -s -o /dev/null -w "%{http_code}" http://localhost:8988 | grep -q "200\|302"; then
    echo -e "${GREEN}✓${NC} HTTP endpoint responding"
else
    echo -e "${RED}✗${NC} HTTP endpoint not responding"
fi

# Test WebSocket port
if curl -s -I http://localhost:8989 2>&1 | grep -q "HTTP"; then
    echo -e "${GREEN}✓${NC} WebSocket port open"
else
    echo -e "${YELLOW}⚠${NC} WebSocket port not accessible"
fi

echo ""

echo -e "${BLUE}━━━ Resource Usage ━━━${NC}"
docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}" | head -5
echo ""

echo -e "${BLUE}━━━ Recent Supervisor Events ━━━${NC}"
docker exec cmms-app tail -10 /var/log/supervisor/supervisord.log | grep -E "(spawned|entered|exited)" || echo "No recent events"
echo ""

echo "╔════════════════════════════════════════════════════════════╗"
echo "║  Status check complete!                                    ║"
echo "╚════════════════════════════════════════════════════════════╝"

