@echo off
REM Script to seed shipping routes locally

echo Seeding shipping routes...
echo.

cd backend
php artisan admin:seed-routes --fresh

echo.
echo Done!
pause
