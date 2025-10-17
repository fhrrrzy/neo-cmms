#!/bin/bash

echo "🔍 Checking Docker CMMS Status"
echo "=============================="
echo ""

cd /home/fhrrrzy/kerjaan/new-cmms

# Check if containers are running
echo "📦 Container Status:"
docker-compose ps
echo ""

# Check if port 8988 is listening
echo "🔌 Port 8988 Status:"
if netstat -tuln 2>/dev/null | grep -q ":8988"; then
    echo "✅ Port 8988 is open"
else
    echo "❌ Port 8988 is not listening"
fi
echo ""

# Try to curl the health endpoint
echo "🏥 Health Check:"
if curl -s -f http://localhost:8988/health > /dev/null 2>&1; then
    echo "✅ Health endpoint responding:"
    curl -s http://localhost:8988/health
else
    echo "❌ Health endpoint not responding"
    echo "   Trying to curl anyway:"
    curl -v http://localhost:8988/health 2>&1 | head -20
fi
echo ""

# Check logs if not healthy
if ! curl -s -f http://localhost:8988/health > /dev/null 2>&1; then
    echo "📋 Recent App Logs:"
    docker-compose logs --tail=30 app
fi


