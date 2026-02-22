# ShipWithGlowie Auto - Setup Guide

## Prerequisites

- Docker (v20.10 or higher)
- Docker Compose (v1.29 or higher)
- Git

## Initial Setup

1. Clone the repository:
```bash
git clone https://github.com/yourusername/ShipWithGlowie-Auto.git
cd ShipWithGlowie-Auto
```

2. Create environment files:
```bash
cp .env.example .env.docker
cp backend/.env.example backend/.env.docker
cp frontend/.env.example frontend/.env
```

3. Configure `.env.docker` with your settings

## Building and Running

1. Build all Docker images:
```bash
./scripts/docker-build.sh
```

2. Start all services:
```bash
./scripts/docker-up.sh
```

3. Check service status:
```bash
docker-compose ps
```

## Database Setup

1. Run migrations:
```bash
./scripts/docker-migrate.sh
```

2. Seed the database:
```bash
./scripts/docker-seed.sh
```

## Accessing Services

- Frontend: http://localhost:5173
- Backend API: http://localhost:8000
- MySQL: localhost:3306
- Redis: localhost:6379
- n8n: http://localhost:5678

## Useful Commands

- View logs: `./scripts/docker-logs.sh`
- Stop services: `./scripts/docker-down.sh`
- Access container shell: `./scripts/docker-shell.sh <service-name>`
