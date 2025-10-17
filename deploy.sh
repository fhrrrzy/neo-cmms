#!/bin/bash
set -e

echo "🚀 CMMS Deployment Script"
echo "========================"
echo ""

cd /home/fhrrrzy/kerjaan/new-cmms

# Step 1: Build frontend assets
echo "📦 Step 1/3: Building frontend assets..."
pnpm run build
echo "✅ Frontend assets built"
echo ""

# Step 2: Build Docker images
echo "🐳 Step 2/3: Building Docker images..."
docker-compose build --no-cache
echo "✅ Docker images built"
echo ""

# Step 3: Deploy containers
echo "🚢 Step 3/3: Deploying containers..."
docker-compose down
docker-compose up -d
echo "✅ Containers deployed"
echo ""

# Wait for health check
echo "⏳ Waiting for containers to be healthy..."
sleep 15

# Show status
echo "📊 Container Status:"
docker-compose ps
echo ""

# Test endpoints
echo "🔍 Testing endpoints:"
echo "HTTP (8988):"
curl -s http://localhost:8988/health || echo "❌ Failed"
echo ""
echo "WebSocket (8989):"
curl -I http://localhost:8989 2>&1 | head -3 || echo "❌ Failed"
echo ""

echo "✅ Deployment complete!"
echo ""
echo "🌐 Access your application:"
echo "   - HTTP: http://localhost:8988"
echo "   - WebSocket: ws://localhost:8989"

