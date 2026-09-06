#!/usr/bin/env sh

set -eu

# Освобождаем порт 80 для standalone-проверки Certbot.
cd /opt/scheduler
docker compose --env-file .env.production -f docker-compose.production.yml stop nginx
