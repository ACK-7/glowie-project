#!/bin/bash
echo "Building Docker images..."
docker-compose build

echo "Starting services..."
docker-compose up -d

echo "Running migrations..."
docker-compose exec -T backend php artisan migrate --force

echo "Seeding database..."
docker-compose exec -T backend php artisan db:seed

echo "All services are running!"
echo "Frontend: http://localhost:5173"
echo "Backend: http://localhost:8000"
echo "n8n: http://localhost:5678"
