#!/bin/bash

# Laravel CMMS Docker Setup Script
set -e

echo "🚀 Setting up Laravel CMMS with Docker..."

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cp .env.example .env
    echo "✅ .env file created from .env.example"
else
    echo "✅ .env file already exists"
fi

# Generate application key if not set
if ! grep -q "APP_KEY=" .env || grep -q "APP_KEY=$" .env; then
    echo "🔑 Generating application key..."
    docker run --rm -v $(pwd):/app -w /app php:8.4-cli php artisan key:generate
    echo "✅ Application key generated"
else
    echo "✅ Application key already set"
fi

# Build and start containers
echo "🐳 Building and starting Docker containers..."
docker-compose up --build -d

# Wait for containers to be ready
echo "⏳ Waiting for containers to be ready..."
sleep 10

# Check container status
echo "📊 Container status:"
docker-compose ps

# Run migrations if database is configured
if grep -q "DB_CONNECTION=" .env && ! grep -q "DB_CONNECTION=$" .env; then
    echo "🗄️  Running database migrations..."
    docker-compose exec -T app php artisan migrate --force
    echo "✅ Database migrations completed"
else
    echo "⚠️  No database configured, skipping migrations"
fi

echo ""
echo "🎉 Setup completed successfully!"
echo ""
echo "📋 Next steps:"
echo "   • Access your application at: http://localhost"
echo "   • View logs: docker-compose logs -f"
echo "   • Run artisan commands: docker-compose exec app php artisan <command>"
echo "   • Stop services: docker-compose down"
echo ""
echo "📖 For more information, see SETUP.md"
