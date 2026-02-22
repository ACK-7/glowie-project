#!/bin/bash
echo "Seeding database..."
docker-compose exec -T backend php artisan db:seed
echo "Database seeded successfully!"
