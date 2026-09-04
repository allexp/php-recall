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
