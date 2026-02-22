@echo off
REM Simple script to seed admin users using PHP artisan command

echo 🚀 Seeding admin users...
echo.

cd backend
if errorlevel 1 (
    echo ❌ Failed to navigate to backend directory
    exit /b 1
)

echo Running: php artisan admin:seed-users --fresh
echo.
php artisan admin:seed-users --fresh

if errorlevel 1 (
    echo.
    echo ❌ Seeding failed!
    echo.
    echo Make sure:
    echo    1. You're in the backend directory
    echo    2. Database connection is configured in .env
    echo    3. MySQL is running (Docker or local)
    echo.
    exit /b 1
)

echo.
echo ✅ Done! You can now login with the credentials shown above.
pause
