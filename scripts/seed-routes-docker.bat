@echo off
REM Script to seed shipping routes in Docker environment

echo Seeding shipping routes...
echo.

docker exec shipwithglowie-backend php artisan admin:seed-routes --fresh

echo.
echo Done!
pause
