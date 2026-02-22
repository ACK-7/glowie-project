#!/bin/bash
echo "Running migrations..."
docker-compose exec -T backend php artisan migrate --force
echo "Migrations completed!"
