# PHP Recall на Symfony

Независимая Symfony 7.4 LTS-версия тренажёра. Исходное приложение в родительском каталоге не изменяется.

Планируемые изменения описаны в [плане развития](ROADMAP.md).

## Запуск

```bash
docker compose up --build -d
```

Приложение будет доступно на <http://localhost:8081>.
PostgreSQL доступен с компьютера на `127.0.0.1:5433`; внутри Docker Compose приложение использует сервис `database`.

После запуска контейнеров и после получения новых изменений примените миграции:

```bash
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

При первом запуске после перехода с SQLite перенесите существующие данные:

```bash
docker compose exec app php bin/console app:database:import-sqlite --skip-missing
```

В production миграции автоматически выполняются скриптом `deploy.sh` после запуска обновлённого контейнера.

При обычном локальном запуске Docker Compose автоматически применяет `compose.override.yaml`.
Каталоги с PHP-кодом, конфигурацией, шаблонами и публичными файлами подключаются в контейнер
напрямую с компьютера, поэтому после первой сборки изменения видны без пересборки образа:

```bash
docker compose up -d
```

Команда с `--build` по-прежнему нужна после изменения `Dockerfile`, `composer.json`,
`composer.lock` или состава системных пакетов и PHP-расширений. Production-конфигурация
override-файл не использует и продолжает собирать неизменяемый образ с исходниками внутри.

В разделе «Песочница» PHP-код выполняется в новом одноразовом контейнере без сети,
с файловой системой только для чтения и ограничениями CPU, памяти, процессов, времени и объёма вывода.
Для каждого запуска необходим доступ runner-сервиса к Docker socket.

После изменения исходников редактора в `assets` пересоберите локальный JavaScript:

```bash
npm install
npm run build
```

Каталог функций, концепции и практические задачи добавляются в PostgreSQL миграциями. Чтобы загрузить документацию всего каталога из PHP Manual, выполните:

```bash
docker compose exec app php bin/console app:import-function-documentation
```

Повторный запуск пропускает уже заполненные материалы. Для принудительного обновления используйте `--refresh`; после команды можно указать имена отдельных функций.

## Администрирование

Панель EasyAdmin доступна по адресу <http://localhost:8081/admin> и использует отдельные учётные записи из таблицы `admin_users` PostgreSQL.

Перед первым входом примените миграции и создайте администратора:

```bash
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec app php bin/console app:admin:create admin@example.com
```

Команда запросит пароль длиной не менее 12 символов. Повторный запуск команды для существующего адреса электронной почты обновит пароль администратора.

## Production

Для запуска в общей Docker-сети существующего Nginx используется отдельная конфигурация:

```bash
docker compose -f compose.production.yaml up --build -d
```

Создайте `.env.production` на основе `.env.production.example`. Обязательно задайте случайные
`APP_SECRET`, `POSTGRES_PASSWORD` и `SANDBOX_TOKEN`, а также числовой `DOCKER_GID` — группу,
владеющую `/var/run/docker.sock` на сервере (её можно узнать через `stat -c '%g' /var/run/docker.sock`).
Пароль в `DATABASE_URL` должен совпадать с `POSTGRES_PASSWORD` и быть URL-кодирован.

В production приложение доступно по адресу <https://php-recall.aleksppv.ru>. Центральный Nginx
должен находиться в сети `scheduler_backend` и проксировать запросы на `php-recall:8080`.
Скрипты из `ops/certbot` используются сервером для автоматического продления TLS-сертификата.

### Обновление сервера

После первоначального клонирования репозитория и создания `.env.production` обновление выполняется deploy-скриптом:

```bash
chmod +x deploy.sh
./deploy.sh
```

Скрипт получает изменения из `origin/master`, пересобирает production-образ, запускает PostgreSQL,
применяет миграции и при первом обновлении переносит данные из прежних SQLite-файлов. PostgreSQL
хранится в Docker volume `php-recall-postgres`, а старый volume `php-recall-data` сохраняется как
источник и резервная копия переноса.

Перенос можно запустить вручную:

```bash
docker compose exec app php bin/console app:database:import-sqlite --skip-missing
```

Команда отмечает успешно выполненный перенос и не запускает его повторно. Для осознанного повторного
импорта используется параметр `--force`; он обновит PostgreSQL данными из SQLite.

Чтобы проверить другой публичный адрес, передайте URL в переменной окружения:

```bash
DEPLOY_HEALTH_URL=https://example.com ./deploy.sh
```

При необходимости remote и ветку можно переопределить переменными `DEPLOY_REMOTE` и `DEPLOY_BRANCH`.
