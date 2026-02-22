#!/bin/bash

# Script to seed the admin database
# This script runs migrations and seeds the database with admin users

echo "🚀 Starting database seeding process..."

# Navigate to backend directory
cd backend || exit

# Check if .env file exists
if [ ! -f .env ]; then
    echo "❌ .env file not found. Please create one first."
    exit 1
fi

# Check if running in Docker or locally
echo "Checking database connection..."
if php -r "try { \$pdo = new PDO('mysql:host=mysql;port=3306', 'shipuser', 'shippass', [PDO::ATTR_TIMEOUT => 2]); echo 'Docker'; } catch (Exception \$e) { echo 'Local'; }" 2>/dev/null | grep -q "Docker"; then
    echo "✓ Running in Docker mode (DB_HOST=mysql)"
    DB_HOST="mysql"
else
    echo "✓ Running in local mode (DB_HOST=127.0.0.1)"
    echo ""
    echo "⚠️  WARNING: If you're running outside Docker, make sure:"
    echo "   1. MySQL is running locally"
    echo "   2. Your .env file has DB_HOST=127.0.0.1 (not 'mysql')"
    echo "   3. Database credentials match your local MySQL setup"
    echo ""
    read -p "Press Enter to continue..."
    DB_HOST="127.0.0.1"
fi

# Test database connection with shorter timeout
echo "Testing database connection (timeout: 3 seconds)..."
if ! timeout 5 php -r "try { \$pdo = new PDO('mysql:host=${DB_HOST};port=3306', 'shipuser', 'shippass', [PDO::ATTR_TIMEOUT => 3, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); echo 'Connection OK'; } catch (PDOException \$e) { echo 'Connection FAILED: ' . \$e->getMessage(); exit(1); } catch (Exception \$e) { echo 'Connection FAILED: ' . \$e->getMessage(); exit(1); }" 2>/dev/null; then
    echo ""
    echo "❌ Database connection failed!"
    echo ""
    echo "Please check:"
    echo "   1. MySQL is running (check services or Docker)"
    echo "   2. .env file has correct DB_HOST (use 127.0.0.1 for local, mysql for Docker)"
    echo "   3. Database credentials are correct"
    echo "   4. For Docker: Run 'docker-compose up -d mysql' first"
    echo "   5. For Local: Ensure MySQL service is started"
    echo ""
    echo "Quick checks:"
    echo "   - Docker: docker ps | grep mysql"
    echo "   - Local: Check if MySQL service is running"
    echo ""
    exit 1
fi

# Test if database exists
echo "Checking if database exists..."
php -r "try { \$pdo = new PDO('mysql:host=${DB_HOST};port=3306;dbname=shipwithglowie', 'shipuser', 'shippass', [PDO::ATTR_TIMEOUT => 3]); echo 'Database exists'; } catch (PDOException \$e) { if (\$e->getCode() == 1049) { echo 'Database does not exist - will be created by migrations'; } else { echo 'Error: ' . \$e->getMessage(); exit(1); } }" 2>/dev/null

# Run migrations
echo ""
echo "📦 Running database migrations..."
php artisan migrate --force
if [ $? -ne 0 ]; then
    echo "❌ Migration failed"
    echo ""
    echo "If you see connection errors, try:"
    echo "   1. For Docker: docker-compose up -d mysql"
    echo "   2. For Local: Update .env DB_HOST=127.0.0.1"
    exit 1
fi

# Run seeders
echo ""
echo "🌱 Seeding database..."
php artisan db:seed --class=EnhancedDatabaseSeeder --force
if [ $? -ne 0 ]; then
    echo "❌ Seeding failed"
    exit 1
fi

echo ""
echo "✅ Database seeding completed!"
echo ""
echo "📋 Admin Login Credentials:"
echo "   Super Admin: admin@shipwithglowie.com / admin123"
echo "   Manager: manager@shipwithglowie.com / manager123"
echo "   Admin: service@shipwithglowie.com / service123"
echo "   Operator: operator@shipwithglowie.com / operator123"
echo ""
echo "🎉 You can now login to the admin dashboard!"
echo "   URL: http://localhost:5173/admin/login"
