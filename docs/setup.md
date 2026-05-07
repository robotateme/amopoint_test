# Локальный запуск и окружение

## Запуск через Sail

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm run dev -- --host 0.0.0.0
```

Упрощенный вариант через `make`:

```bash
make setup
make up
```

`make` автоматически переключается между режимами:

- `sail` — если Docker доступен;
- `local` — если Docker/Podman не запущен или Sail недоступен.

Примеры:

```bash
make test
make RUNTIME=local quality
make RUNTIME=sail up
```

## Порты по умолчанию

```text
Приложение: http://127.0.0.1
Vite:       http://127.0.0.1:5173
PostgreSQL: 127.0.0.1:5432
Redis:      127.0.0.1:6379
```

Для изменения портов используйте переменные:

```dotenv
APP_PORT=80
VITE_PORT=5173
FORWARD_DB_PORT=5432
FORWARD_REDIS_PORT=6379
```

## Переменные доступа к статистике

```dotenv
STATS_LOGIN=admin
STATS_PASSWORD=secret
STATS_JWT_SECRET=
STATS_JWT_TTL=3600
STATS_RATE_LIMIT_REDIS_CONNECTION=cache
STATS_RATE_LIMIT_MAX_ATTEMPTS=5
STATS_RATE_LIMIT_WINDOW_SECONDS=60
```

`/stats` использует JWT в `HttpOnly` cookie или Bearer token.
Лимит на login построен как sliding window через Redis Lua script.

## Запуск без Sail

Подходит, если локально уже установлены PHP 8.5.0, Composer, Node.js, PostgreSQL и Redis.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run dev
php artisan serve
```

В этом режиме проверьте `DB_HOST`, `DB_PORT`, `REDIS_HOST` и `REDIS_PORT`.

Если `make` пишет, что Docker или Podman не запущен, это больше не блокирует локальные команды проверки. Для локального режима нужен PHP 8.5.0:

```bash
make RUNTIME=local test
make RUNTIME=local build
make RUNTIME=local quality
```

## Тестовые данные

Сидер `Database\\Seeders\\VisitSeeder` создает просмотры за последние 24 часа:

```bash
./vendor/bin/sail artisan db:seed --class=VisitSeeder
./vendor/bin/sail artisan db:seed
```

Через `make`:

```bash
make seed
make fresh
```
