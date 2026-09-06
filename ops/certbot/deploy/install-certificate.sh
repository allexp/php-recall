#!/usr/bin/env sh

set -eu

# Обновляем файлы, подключённые в production-контейнер Nginx.
install -m 0644 "$RENEWED_LINEAGE/fullchain.pem" /etc/scheduler/ssl/fullchain.pem
install -m 0600 "$RENEWED_LINEAGE/privkey.pem" /etc/scheduler/ssl/private.key
