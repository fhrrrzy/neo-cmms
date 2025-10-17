#!/bin/bash

echo "🔨 Building Docker Images with Redis Extension"
echo "=============================================="
echo ""

cd /home/fhrrrzy/kerjaan/new-cmms

# Build frontend assets locally first
echo "📦 Building frontend assets locally..."
pnpm run build

echo ""
echo "🐳 Building Docker images..."
docker-compose build --no-cache

echo ""
echo "✅ Build complete! Starting containers..."
docker-compose down
docker-compose up -d

echo ""
echo "⏳ Waiting for containers to be healthy..."
sleep 10

echo ""
echo "📊 Container status:"
docker-compose ps

echo ""
echo "🔍 Testing application..."
echo "Health endpoint:"
curl -s http://localhost:8988/health

echo ""
echo ""
echo "Main endpoint:"
curl -s http://localhost:8988 | head -20

echo ""
echo ""
echo "✅ Done! Access app at http://localhost:8988"

