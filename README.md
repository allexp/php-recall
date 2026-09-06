# PHP Recall на Symfony

Независимая Symfony 7.4 LTS-версия тренажёра. Исходное приложение в родительском каталоге не изменяется.

## Запуск

```bash
docker compose up --build -d
```

Приложение будет доступно на <http://localhost:8081>.

## Production

Для запуска в общей Docker-сети существующего Nginx используется отдельная конфигурация:

```bash
docker compose -f compose.production.yaml up --build -d
```

### Обновление сервера

После первоначального клонирования репозитория и создания `.env.production` обновление выполняется deploy-скриптом:

```bash
chmod +x deploy.sh
./deploy.sh
```

Скрипт получает изменения из `origin/master`, пересобирает production-образ, перезапускает контейнер и проверяет его состояние. SQLite-база остаётся в Docker volume `php-recall-data`.

Для дополнительной проверки публичного адреса передайте URL в переменной окружения:

```bash
DEPLOY_HEALTH_URL=https://example.com ./deploy.sh
```

При необходимости remote и ветку можно переопределить переменными `DEPLOY_REMOTE` и `DEPLOY_BRANCH`.
