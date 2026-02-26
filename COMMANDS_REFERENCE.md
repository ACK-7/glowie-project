# Commands Reference Guide

Quick reference for all important commands in the project.

---

## Backend (Laravel)

### Setup
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

### Development
```bash
# Start server
php artisan serve
php artisan serve --port=8001  # Custom port

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Database
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed

# View routes
php artisan route:list
php artisan route:list --path=api

# Tinker (REPL)
php artisan tinker
```

### Testing
```bash
php artisan test
php artisan test --filter QuoteTest
```

---

## Frontend (React)

### Setup
```bash
cd frontend
npm install
cp .env.example .env
```

### Development
```bash
# Start dev server
npm run dev
npm run dev -- --port 3000  # Custom port

# Build for production
npm run build

# Preview production build
npm run preview

# Lint
npm run lint
```

---

## AI Service (Python)

### Setup
```bash
cd ai-service

# Run setup script
python setup.py

# Create virtual environment
python -m venv venv

# Activate virtual environment
# Windows:
venv\Scripts\activate
# Linux/Mac:
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt

# Create .env from example
cp .env.example .env
```

### Development
```bash
# Start service
uvicorn main:app --reload --port 8001

# Start with custom settings
uvicorn main:app --reload --host 0.0.0.0 --port 8002

# Start without reload (production-like)
uvicorn main:app --host 0.0.0.0 --port 8001
```

### Testing
```bash
# Run all tests
pytest

# Run with coverage
pytest --cov=. --cov-report=html

# Run specific test
pytest tests/test_quote_agent.py -v

# Run with output
pytest -s
```

### Code Quality
```bash
# Format code
black .

# Lint
flake8 .

# Type checking
mypy .
```

---

## Docker

### Full Stack
```bash
# Start all services
docker-compose up

# Start in background
docker-compose up -d

# Build and start
docker-compose up --build

# Stop all services
docker-compose down

# View logs
docker-compose logs -f
docker-compose logs -f backend
docker-compose logs -f ai-service

# Restart specific service
docker-compose restart backend
docker-compose restart ai-service
```

### AI Service Only
```bash
cd ai-service

# Build
docker build -t ai-service .

# Run
docker run -p 8001:8001 --env-file .env ai-service

# Run with volume
docker run -p 8001:8001 -v $(pwd):/app --env-file .env ai-service
```

---

## Database

### MySQL Commands
```bash
# Connect to MySQL
mysql -u root -p

# Create database
CREATE DATABASE shipwithglowie;

# Show databases
SHOW DATABASES;

# Use database
USE shipwithglowie;

# Show tables
SHOW TABLES;

# Describe table
DESCRIBE shipments;
```

### Laravel Database
```bash
# Run migrations
php artisan migrate

# Rollback
php artisan migrate:rollback

# Fresh migration
php artisan migrate:fresh

# Fresh with seed
php artisan migrate:fresh --seed

# Seed only
php artisan db:seed

# Specific seeder
php artisan db:seed --class=CarsSeeder
```

---

## Redis

### Redis CLI
```bash
# Connect to Redis
redis-cli

# Test connection
PING

# Get all keys
KEYS *

# Get value
GET key_name

# Delete key
DEL key_name

# Flush all
FLUSHALL
```

---

## Git

### Common Commands
```bash
# Status
git status

# Add files
git add .
git add specific-file.txt

# Commit
git commit -m "Your message"

# Push
git push origin main

# Pull
git pull origin main

# Create branch
git checkout -b feature-name

# Switch branch
git checkout main

# Merge branch
git merge feature-name

# View log
git log --oneline
```

---

## Testing APIs

### cURL Commands

#### Health Checks
```bash
# Backend health
curl http://localhost:8000/api/health

# AI Service health
curl http://localhost:8001/health
```

#### Quote Generation
```bash
# Generate quote
curl -X POST http://localhost:8001/agents/quote \
  -H "Content-Type: application/json" \
  -d '{
    "vehicle_type": "sedan",
    "year": 2020,
    "make": "Toyota",
    "model": "Camry",
    "origin_country": "Japan",
    "shipping_method": "roro"
  }'
```

#### Shipment Tracking
```bash
# Track shipment
curl http://localhost:8000/api/tracking/TRK00000003
```

### PowerShell (Windows)
```powershell
# Health check
Invoke-WebRequest -Uri "http://localhost:8001/health" | Select-Object -ExpandProperty Content

# Quote generation
$body = @{
    vehicle_type = "sedan"
    year = 2020
    make = "Toyota"
    model = "Camry"
    origin_country = "Japan"
    shipping_method = "roro"
} | ConvertTo-Json

Invoke-WebRequest -Uri "http://localhost:8001/agents/quote" `
    -Method POST `
    -ContentType "application/json" `
    -Body $body
```

---

## Environment Management

### Copy Environment Files
```bash
# Backend
cp backend/.env.example backend/.env

# Frontend
cp frontend/.env.example frontend/.env

# AI Service
cp ai-service/.env.example ai-service/.env
```

### Edit Environment Files
```bash
# Windows
notepad backend/.env
notepad frontend/.env
notepad ai-service/.env

# Linux/Mac
nano backend/.env
nano frontend/.env
nano ai-service/.env
```

---

## Logs

### View Logs
```bash
# Backend logs
tail -f backend/storage/logs/laravel.log

# AI Service logs
tail -f ai-service/logs/ai-service.log

# Docker logs
docker-compose logs -f backend
docker-compose logs -f ai-service
```

### Clear Logs
```bash
# Backend
rm backend/storage/logs/*.log

# AI Service
rm ai-service/logs/*.log
```

---

## Port Management

### Check Port Usage
```bash
# Windows
netstat -ano | findstr :8000
netstat -ano | findstr :8001
netstat -ano | findstr :5173

# Linux/Mac
lsof -i :8000
lsof -i :8001
lsof -i :5173
```

### Kill Process on Port
```bash
# Windows
# Find PID first, then:
taskkill /PID <PID> /F

# Linux/Mac
kill -9 $(lsof -t -i:8000)
```

---

## Useful Aliases (Optional)

Add to your shell profile (.bashrc, .zshrc, or PowerShell profile):

```bash
# Backend
alias be-start="cd backend && php artisan serve"
alias be-migrate="cd backend && php artisan migrate"
alias be-seed="cd backend && php artisan db:seed"
alias be-fresh="cd backend && php artisan migrate:fresh --seed"

# Frontend
alias fe-start="cd frontend && npm run dev"
alias fe-build="cd frontend && npm run build"

# AI Service
alias ai-start="cd ai-service && source venv/bin/activate && uvicorn main:app --reload --port 8001"
alias ai-test="cd ai-service && pytest"

# Docker
alias dc-up="docker-compose up -d"
alias dc-down="docker-compose down"
alias dc-logs="docker-compose logs -f"
```

---

## Quick Start Commands

### Start Everything (Development)
```bash
# Terminal 1 - Backend
cd backend && php artisan serve

# Terminal 2 - Frontend
cd frontend && npm run dev

# Terminal 3 - AI Service
cd ai-service && venv\Scripts\activate && uvicorn main:app --reload --port 8001
```

### Stop Everything
```bash
# Press Ctrl+C in each terminal
```

---

## Production Commands

### Build for Production
```bash
# Frontend
cd frontend && npm run build

# Backend (optimize)
cd backend
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache

# AI Service
cd ai-service
pip install -r requirements.txt --no-cache-dir
```

### Deploy
```bash
# Pull latest code
git pull origin main

# Update dependencies
cd backend && composer install
cd frontend && npm install && npm run build
cd ai-service && pip install -r requirements.txt

# Run migrations
cd backend && php artisan migrate --force

# Restart services
sudo systemctl restart backend
sudo systemctl restart ai-service
```

---

## Troubleshooting Commands

### Clear Everything
```bash
# Backend
cd backend
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload

# Frontend
cd frontend
rm -rf node_modules
rm package-lock.json
npm install

# AI Service
cd ai-service
rm -rf venv
python -m venv venv
venv\Scripts\activate
pip install -r requirements.txt
```

### Reset Database
```bash
cd backend
php artisan migrate:fresh --seed
```

### Reinstall Dependencies
```bash
# Backend
cd backend && rm -rf vendor && composer install

# Frontend
cd frontend && rm -rf node_modules && npm install

# AI Service
cd ai-service && rm -rf venv && python -m venv venv && pip install -r requirements.txt
```

---

## Monitoring Commands

### Check Service Status
```bash
# Check if services are running
curl -s http://localhost:8000/api/health && echo "Backend: OK" || echo "Backend: DOWN"
curl -s http://localhost:5173 && echo "Frontend: OK" || echo "Frontend: DOWN"
curl -s http://localhost:8001/health && echo "AI Service: OK" || echo "AI Service: DOWN"
```

### Monitor Resources
```bash
# CPU and Memory
top
htop

# Disk usage
df -h

# Process list
ps aux | grep php
ps aux | grep node
ps aux | grep python
```

---

**Keep this file handy for quick reference!**
