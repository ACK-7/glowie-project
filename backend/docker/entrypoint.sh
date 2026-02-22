#!/bin/sh
set -e

echo "[$(date)] Starting backend entrypoint script..."

DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-shipwithglowie}"
DB_USERNAME="${DB_USERNAME:-shipuser}"
DB_PASSWORD="${DB_PASSWORD:-shippass}"
APP_PORT="${BACKEND_PORT:-8000}"

# Step 1: Wait for database with better diagnostics
echo "[$(date)] Waiting for MySQL to be ready at ${DB_HOST}:${DB_PORT}..."
max_attempts=60
attempt=1

while [ $attempt -le $max_attempts ]; do
    if nc -z -w 2 "$DB_HOST" "$DB_PORT" > /dev/null 2>&1; then
        echo "[$(date)] ✓ MySQL port ${DB_PORT} is responding"
        
        if php -r "
            \$host = getenv('DB_HOST') ?: 'mysql';
            \$port = getenv('DB_PORT') ?: '3306';
            \$db   = getenv('DB_DATABASE') ?: 'shipwithglowie';
            \$user = getenv('DB_USERNAME') ?: 'shipuser';
            \$pass = getenv('DB_PASSWORD') ?: 'shippass';
            try {
                \$pdo = new PDO(
                    \"mysql:host=\$host;port=\$port;dbname=\$db\",
                    \$user,
                    \$pass,
                    [PDO::ATTR_TIMEOUT => 5]
                );
                \$result = \$pdo->query('SELECT 1');
                if (\$result && \$result->fetch()) {
                    echo 'Database connection successful';
                    exit(0);
                }
                echo 'DB query did not return a result';
                exit(1);
            } catch (Exception \$e) {
                echo 'DB Error: ' . \$e->getMessage();
                exit(1);
            }
        " 2>&1; then
            echo "[$(date)] ✓ Database is ready and responding"
            break
        fi
    fi
    
    if [ $((attempt % 10)) -eq 0 ]; then
        echo "[$(date)] Attempt $attempt/$max_attempts: MySQL not ready yet..."
    fi
    
    sleep 1
    attempt=$((attempt + 1))
done

if [ $attempt -gt $max_attempts ]; then
    echo "[$(date)] ✗ MySQL failed to respond after ${max_attempts}s"
    echo "[$(date)] Debugging info:"
    echo "[$(date)] Trying to connect with provided credentials..."
    php -r "
        \$host = getenv('DB_HOST') ?: 'mysql';
        \$port = getenv('DB_PORT') ?: '3306';
        \$db   = getenv('DB_DATABASE') ?: 'shipwithglowie';
        \$user = getenv('DB_USERNAME') ?: 'shipuser';
        \$pass = getenv('DB_PASSWORD') ?: 'shippass';
        try {
            \$pdo = new PDO(\"mysql:host=\$host;port=\$port;dbname=\$db\", \$user, \$pass);
            echo 'Connection successful';
        } catch (Exception \$e) {
            echo 'Connection failed: ' . \$e->getMessage();
        }
    " || true
    exit 1
fi

# Step 2: Generate APP_KEY if missing
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "[$(date)] Generating APP_KEY..."
    php artisan key:generate --force || true
fi

# Step 3: Clear caches
echo "[$(date)] Clearing application caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan view:clear || true

# Step 4: Cache config
echo "[$(date)] Caching configuration..."
php artisan config:cache || true

# Step 5: Run migrations
echo "[$(date)] Running database migrations..."
if php artisan migrate --force 2>&1; then
    echo "[$(date)] ✓ Migrations completed successfully"
else
    echo "[$(date)] ⚠ Migration warning (may already be migrated)"
fi

# Step 6: Run seeders (optional)
if [ "${DB_SEED_AUTO}" = "true" ]; then
    echo "[$(date)] Running database seeders..."
    php artisan db:seed --force || echo "[$(date)] ⚠ Seeding completed with warnings"
fi

# Step 7: Create storage symlink if needed
if [ ! -L "public/storage" ]; then
    echo "[$(date)] Creating storage symlink..."
    php artisan storage:link || true
fi

echo "[$(date)] ✓ All startup tasks completed"
echo "[$(date)] Starting PHP built-in server on 0.0.0.0:${APP_PORT} (docroot: public)..."
echo ""

# Step 8: Start Laravel server in foreground (CRITICAL - must not background this)
# The 'exec' replaces the shell process with the PHP process (PID 1)
exec php -S 0.0.0.0:"${APP_PORT}" -t public public/index.php 2>&1
