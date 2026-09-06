#!/usr/bin/env sh

set -eu

# Возвращаем Nginx после завершения попытки продления.
cd /opt/scheduler
docker compose --env-file .env.production -f docker-compose.production.yml up -d nginx
