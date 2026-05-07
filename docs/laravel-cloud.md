# Деплой на Laravel Cloud

Проект можно деплоить в Laravel Cloud из GitHub репозитория `robotateme/amopoint_test`.

## Настройки окружения

Минимальные переменные:

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<cloud-domain>
LOG_CHANNEL=stderr
QUEUE_CONNECTION=sync
SESSION_DRIVER=cookie
CACHE_STORE=database
STATS_LOGIN=admin
STATS_PASSWORD=<secret>
STATS_JWT_SECRET=<random-secret>
SOCKET_IO_ENABLED=false
```

Подключите managed PostgreSQL в Laravel Cloud. Cloud автоматически добавит переменные подключения к БД.

## Build commands

```bash
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan optimize
```

## Deploy commands

```bash
php artisan migrate --force
```

## Автодеплой из GitHub

В Laravel Cloud включите deploy hook и добавьте URL в GitHub repository secret:

```text
LARAVEL_CLOUD_DEPLOY_HOOK
```

После этого workflow `.github/workflows/laravel-cloud-deploy.yml` будет триггерить Laravel Cloud deploy при каждом push в `main`.

## Realtime

Текущий Socket.IO sidecar рассчитан на Docker/nginx окружение и выключен для Laravel Cloud через `SOCKET_IO_ENABLED=false`.
Dashboard сохраняет fallback обновления статистики по таймеру. Для managed realtime на Laravel Cloud следующий шаг - перевести статистику на Laravel Reverb / Echo и подключить Cloud WebSockets.
