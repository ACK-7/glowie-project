# ShipWithGlowie Auto - Deployment Guide

## Docker Hub Deployment

1. Create Docker Hub account and repository

2. Build and tag images:
```bash
docker build -t yourusername/shipwithglowie-backend:latest ./backend
docker build -t yourusername/shipwithglowie-frontend:latest ./frontend
```

3. Push to Docker Hub:
```bash
docker push yourusername/shipwithglowie-backend:latest
docker push yourusername/shipwithglowie-frontend:latest
```

## Production Deployment

1. Set up production environment file:
```bash
cp .env.docker .env.prod
# Update with production values
```

2. Use production compose file:
```bash
docker-compose -f docker-compose.prod.yml up -d
```

3. Configure SSL/TLS:
```bash
# Use reverse proxy (Nginx/Traefik) with Let's Encrypt
```

## GitHub Actions CI/CD

The project includes GitHub Actions workflows for automated building and deployment.

### Secrets Required

Set the following secrets in your GitHub repository:
- `DOCKER_USERNAME`: Your Docker Hub username
- `DOCKER_PASSWORD`: Your Docker Hub password

## Health Checks

All services include health checks configured in docker-compose.yml.

## Scaling

For production environments:
- Use load balancer (Nginx, Traefik, or cloud provider)
- Scale backend services: `docker-compose up -d --scale backend=3`
- Use managed database services for reliability
