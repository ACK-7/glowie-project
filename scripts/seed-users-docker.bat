@echo off
REM Seed admin users using Docker container

echo 🚀 Seeding admin users via Docker...
echo.

REM Check if backend container is running
docker ps --filter "name=backend" --format "{{.Names}}" | findstr /C:"shipwithglowie-backend" >nul 2>&1
if errorlevel 1 (
    echo ⚠️  Backend container not running. Starting it...
    docker-compose up -d backend
    timeout /t 5 /nobreak >nul
)

echo Running command inside Docker container...
docker exec shipwithglowie-backend php artisan admin:seed-users --fresh

if errorlevel 1 (
    echo.
    echo ❌ Seeding failed!
    echo.
    echo Make sure:
    echo    1. Docker containers are running
    echo    2. MySQL container is healthy
    echo.
    exit /b 1
)

echo.
echo ✅ Done! You can now login with the credentials shown above.
pause
