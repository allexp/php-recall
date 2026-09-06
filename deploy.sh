#!/usr/bin/env sh

set -eu

# Скрипт можно запускать из любого каталога: рабочая директория определяется по его расположению.
PROJECT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
COMPOSE_FILE="$PROJECT_DIR/compose.production.yaml"
REMOTE_NAME=${DEPLOY_REMOTE:-origin}
BRANCH_NAME=${DEPLOY_BRANCH:-master}
HEALTH_URL=${DEPLOY_HEALTH_URL:-}

cd "$PROJECT_DIR"

if ! command -v git >/dev/null 2>&1; then
    echo "Ошибка: на сервере не установлен Git." >&2
    exit 1
fi

if ! command -v docker >/dev/null 2>&1; then
    echo "Ошибка: на сервере не установлен Docker." >&2
    exit 1
fi

if ! docker compose version >/dev/null 2>&1; then
    echo "Ошибка: недоступна команда docker compose." >&2
    exit 1
fi

if [ ! -f "$COMPOSE_FILE" ]; then
    echo "Ошибка: не найден файл compose.production.yaml." >&2
    exit 1
fi

if [ ! -f "$PROJECT_DIR/.env.production" ]; then
    echo "Ошибка: не найден файл .env.production." >&2
    exit 1
fi

if ! git diff --quiet || ! git diff --cached --quiet; then
    echo "Ошибка: на сервере есть незакоммиченные изменения." >&2
    exit 1
fi

echo "Получаю изменения из $REMOTE_NAME/$BRANCH_NAME..."
git fetch "$REMOTE_NAME" "$BRANCH_NAME"
git merge --ff-only "$REMOTE_NAME/$BRANCH_NAME"

echo "Собираю production-образ..."
docker compose -f "$COMPOSE_FILE" build --pull

echo "Запускаю обновлённый контейнер..."
docker compose -f "$COMPOSE_FILE" up -d --remove-orphans

echo "Проверяю состояние сервиса..."
ATTEMPT=1
MAX_ATTEMPTS=15
while [ "$ATTEMPT" -le "$MAX_ATTEMPTS" ]; do
    if docker compose -f "$COMPOSE_FILE" ps --status running --services | grep -Fxq app; then
        break
    fi

    if [ "$ATTEMPT" -eq "$MAX_ATTEMPTS" ]; then
        echo "Ошибка: сервис app не перешёл в состояние running." >&2
        docker compose -f "$COMPOSE_FILE" ps >&2
        docker compose -f "$COMPOSE_FILE" logs --tail 100 app >&2
        exit 1
    fi

    ATTEMPT=$((ATTEMPT + 1))
    sleep 2
done

if [ -n "$HEALTH_URL" ]; then
    if ! command -v curl >/dev/null 2>&1; then
        echo "Ошибка: для HTTP-проверки DEPLOY_HEALTH_URL требуется curl." >&2
        exit 1
    fi

    echo "Проверяю HTTP-ответ $HEALTH_URL..."
    curl --fail --silent --show-error --retry 5 --retry-delay 2 "$HEALTH_URL" >/dev/null
fi

docker compose -f "$COMPOSE_FILE" ps
echo "Развёртывание успешно завершено: $(git rev-parse --short HEAD)."
