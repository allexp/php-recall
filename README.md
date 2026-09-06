# PHP Recall на Symfony

Независимая Symfony 7.4 LTS-версия тренажёра. Исходное приложение в родительском каталоге не изменяется.

Планируемые изменения описаны в [плане развития](ROADMAP.md).

## Запуск

```bash
docker compose up --build -d
```

Приложение будет доступно на <http://localhost:8081>.

Каталог функций автоматически синхронизируется с SQLite при запуске. Чтобы заранее загрузить документацию всего каталога из PHP Manual, выполните:

```bash
docker compose exec app php bin/console app:import-function-documentation
```

Повторный запуск пропускает уже заполненные материалы. Для принудительного обновления используйте `--refresh`; после команды можно указать имена отдельных функций.

## Production

Для запуска в общей Docker-сети существующего Nginx используется отдельная конфигурация:

```bash
docker compose -f compose.production.yaml up --build -d
```

В production приложение доступно по адресу <https://php-recall.aleksppv.ru>. Центральный Nginx
должен находиться в сети `scheduler_backend` и проксировать запросы на `php-recall:8080`.
Скрипты из `ops/certbot` используются сервером для автоматического продления TLS-сертификата.

### Обновление сервера

После первоначального клонирования репозитория и создания `.env.production` обновление выполняется deploy-скриптом:

```bash
chmod +x deploy.sh
./deploy.sh
```

Скрипт получает изменения из `origin/master`, пересобирает production-образ, перезапускает контейнер и проверяет его состояние и публичный адрес <https://php-recall.aleksppv.ru>. SQLite-база остаётся в Docker volume `php-recall-data`.

Чтобы проверить другой публичный адрес, передайте URL в переменной окружения:

```bash
DEPLOY_HEALTH_URL=https://example.com ./deploy.sh
```

При необходимости remote и ветку можно переопределить переменными `DEPLOY_REMOTE` и `DEPLOY_BRANCH`.
