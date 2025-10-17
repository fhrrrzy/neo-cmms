#!/bin/bash

# Docker Setup Test Script
# Tests the multi-stage build and container functionality

set -e

echo "🚀 Docker Multi-Stage Build Test"
echo "================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test functions
test_step() {
    echo -e "${YELLOW}➜${NC} $1"
}

test_success() {
    echo -e "${GREEN}✓${NC} $1"
}

test_error() {
    echo -e "${RED}✗${NC} $1"
    exit 1
}

# 1. Check Docker
test_step "Checking Docker installation..."
if ! command -v docker &> /dev/null; then
    test_error "Docker is not installed"
fi
test_success "Docker found: $(docker --version)"

# 2. Check Docker Compose
test_step "Checking Docker Compose..."
if ! command -v docker-compose &> /dev/null; then
    test_error "Docker Compose is not installed"
fi
test_success "Docker Compose found: $(docker-compose --version)"

# 3. Check required files
test_step "Checking required files..."
required_files=(
    "Dockerfile"
    "docker-compose.yml"
    "package.json"
    "composer.json"
    "vite.config.ts"
    "docker/nginx/default.conf"
)

for file in "${required_files[@]}"; do
    if [ ! -f "$file" ]; then
        test_error "Required file missing: $file"
    fi
done
test_success "All required files present"

# 4. Check .env file
test_step "Checking .env file..."
if [ ! -f ".env" ]; then
    if [ -f "env.docker.example" ]; then
        echo "  Creating .env from env.docker.example..."
        cp env.docker.example .env
        test_success ".env created"
    else
        test_error ".env file missing and no example found"
    fi
else
    test_success ".env file exists"
fi

# 5. Validate Dockerfile stages
test_step "Validating Dockerfile stages..."
if ! grep -q "FROM node:.*AS frontend-builder" Dockerfile; then
    test_error "Frontend builder stage not found in Dockerfile"
fi
if ! grep -q "FROM php:.*AS php-builder" Dockerfile; then
    test_error "PHP builder stage not found in Dockerfile"
fi
test_success "All Dockerfile stages present"

# 6. Validate nginx config
test_step "Checking nginx configuration..."
if ! grep -q "fastcgi_pass 127.0.0.1:9000" docker/nginx/default.conf; then
    test_error "Nginx not configured for local PHP-FPM"
fi
test_success "Nginx configuration valid"

# 7. Test build (optional - commented out by default as it takes time)
if [ "$1" == "--build" ]; then
    test_step "Building Docker images (this may take a while)..."
    if docker-compose build --no-cache; then
        test_success "Docker build successful"
    else
        test_error "Docker build failed"
    fi

    # 8. Test start
    test_step "Starting containers..."
    if docker-compose up -d; then
        test_success "Containers started"
    else
        test_error "Failed to start containers"
    fi

    # 9. Wait for health
    test_step "Waiting for application to be ready..."
    sleep 10

    # 10. Test health endpoint
    test_step "Testing health endpoint..."
    if curl -s http://localhost:8081/health | grep -q "healthy"; then
        test_success "Health check passed"
    else
        test_error "Health check failed"
    fi

    # 11. Check if nginx is running
    test_step "Checking if nginx is running..."
    if docker exec cmms-app pgrep nginx > /dev/null; then
        test_success "Nginx is running"
    else
        test_error "Nginx is not running"
    fi

    # 12. Check if PHP-FPM is running
    test_step "Checking if PHP-FPM is running..."
    if docker exec cmms-app pgrep php-fpm > /dev/null; then
        test_success "PHP-FPM is running"
    else
        test_error "PHP-FPM is not running"
    fi

    # Cleanup
    echo ""
    echo "Cleaning up..."
    docker-compose down
fi

echo ""
echo -e "${GREEN}================================${NC}"
echo -e "${GREEN}✓ All tests passed!${NC}"
echo -e "${GREEN}================================${NC}"
echo ""
echo "To run full build test, use: ./docker-test.sh --build"
echo "To start the application, run: make up"
echo ""



