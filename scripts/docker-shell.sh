#!/bin/bash
if [ -z "$1" ]; then
  echo "Usage: $0 <service-name>"
  echo "Available services: backend, frontend, mysql, redis, n8n"
  exit 1
fi

SERVICE=$1
echo "Accessing shell for $SERVICE..."
docker-compose exec $SERVICE /bin/bash
