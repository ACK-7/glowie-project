@echo off
REM Quick script to check MySQL status

echo Checking MySQL status...
echo.

REM Check if Docker MySQL is running
echo [1] Checking Docker MySQL container...
docker ps --filter "name=mysql" --format "table {{.Names}}\t{{.Status}}" 2>nul
if errorlevel 1 (
    echo    Docker not running or MySQL container not found
) else (
    echo    ✓ Docker MySQL container found
)
echo.

REM Check if local MySQL port is open
echo [2] Checking local MySQL port (3306)...
netstat -an | findstr ":3306" >nul 2>&1
if errorlevel 1 (
    echo    ✗ Port 3306 is not in use (MySQL may not be running)
) else (
    echo    ✓ Port 3306 is in use (MySQL may be running)
)
echo.

REM Try to connect
echo [3] Testing MySQL connection...
php -r "try { $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '', [PDO::ATTR_TIMEOUT => 2]); echo '    ✓ Connected to MySQL (root user)'; } catch (Exception $e) { echo '    ✗ Cannot connect: ' . $e->getMessage(); }" 2>nul
echo.

echo Recommendations:
echo - If using Docker: docker-compose up -d mysql
echo - If using local MySQL: Start MySQL service in Windows Services
echo - Check .env file: DB_HOST should be 127.0.0.1 for local, mysql for Docker
echo.

pause
