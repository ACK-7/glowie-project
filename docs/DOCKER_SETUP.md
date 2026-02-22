# ShipWithGlowie Auto - Docker Setup Guide

## Overview

This guide explains the Docker configuration for the ShipWithGlowie Auto platform.

## Services

### MySQL Database
- **Image**: mysql:8.0
- **Port**: 3306
- **Volume**: mysql_data
- **Configuration**: Custom my.cnf for UTF8MB4 and performance tuning

### Redis Cache
- **Image**: redis:7-alpine
- **Port**: 6379
- **Volume**: redis_data
- **Purpose**: Caching, sessions, queues

### Laravel Backend
- **Build**: Custom Dockerfile
- **Port**: 8000
- **Dependencies**: MySQL, Redis
- **Command**: Laravel development server

### React Frontend
- **Build**: Custom Dockerfile with Vite
- **Port**: 5173
- **Build Args**: API URL, app name
- **Nginx**: Production-ready static file serving

### n8n AI Workflows
- **Image**: n8nio/n8n:latest
- **Port**: 5678
- **Database**: MySQL
- **Volume**: n8n_data
- **Authentication**: Basic auth configured

## Docker Compose Configuration

### Development (docker-compose.yml)
- Health checks for all services
- Volume mounts for development
- Environment variable configuration
- Network isolation

### Production (docker-compose.prod.yml)
- Optimized for production
- Persistent volumes
- Resource limits
- No development mounts

## Networking

All services communicate through a dedicated Docker network:
- `shipwithglowie-network` (development)
- `shipwithglowie-prod-network` (production)

## Volumes

### Persistent Data
- `mysql_data`: Database files
- `redis_data`: Cache data
- `n8n_data`: Workflow configurations
- `backend_storage`: File uploads and logs

### Development Mounts
- Source code directories mounted for live reloading
- Node modules excluded for performance

## Environment Variables

### Required Variables
- Database credentials
- Application keys
- Service ports
- API endpoints

### File Structure
- `.env.docker`: Development environment
- `.env.prod`: Production environment
- `.env.example`: Template file

## Building and Deployment

### Local Development
```bash
# Build all images
docker-compose build

# Start services
docker-compose up -d

# View logs
docker-compose logs -f
```

### Production Deployment
```bash
# Use production compose file
docker-compose -f docker-compose.prod.yml up -d

# Scale services if needed
docker-compose up -d --scale backend=3
```

## Troubleshooting

### Common Issues
- Port conflicts: Check if ports are available
- Permission issues: Ensure proper file permissions
- Build failures: Clear Docker cache and rebuild

### Useful Commands
```bash
# View service status
docker-compose ps

# Access container shell
docker-compose exec backend bash

# View specific service logs
docker-compose logs backend

# Clean up
docker-compose down -v
