# Эксплуатация и quality gates

## Redis cache

Redis используется как cache store Laravel:

```dotenv
CACHE_STORE=redis
REDIS_HOST=redis
```

Статистика посещений кешируется на одну минуту. При записи нового визита повышается version key, поэтому агрегаты инвалидируются без очистки всего кеша.

Dashboard обновляет статистику сразу после монтирования, затем раз в 2 секунды, при возврате фокуса вкладки и при событии `storage` от `visit-counter.js`. После успешного `POST /api/visits` счетчик пишет `amopoint_visit_recorded_at` в `localStorage`, чтобы открытая вкладка `/stats` могла сразу запросить свежие агрегаты.

Redis также используется для sliding-window rate limiting на `/stats/login` через Lua script resolver с `SCRIPT LOAD` и `EVALSHA`.

Проверка ключей:

```bash
./vendor/bin/sail redis -n 1 KEYS '*visit-statistics*'
```

## Frontend-сборка

```bash
./vendor/bin/sail npm run dev -- --host 0.0.0.0
./vendor/bin/sail npm run build
```

## Quality gates

Основные проверки:

```bash
make format
make format-check
make test
make analyse
make build
make quality
```

В CI используется GitHub Actions workflow:

- `.github/workflows/ci.yml`
- триггеры: `push` в `main` и `pull_request`
- jobs: `format`, `php-tests`, `static-analysis`, `frontend-build`
- основной runtime: `RUNTIME=local`

Без `make`:

```bash
./vendor/bin/sail artisan test
./vendor/bin/sail php vendor/bin/phpstan analyse --memory-limit=1G
./vendor/bin/sail php vendor/bin/psalm --show-info=false
./vendor/bin/sail npm run build
./vendor/bin/pint --test
```

## Команды Makefile

```bash
make help
make up
make down
make migrate
make seed
make fresh
make routes
```

`make help` выводит цветной список целей с краткими описаниями.

`make format` исправляет стиль, а `make format-check` только валидирует его без изменений.

По умолчанию `make` сам выбирает runtime:

- `sail` при доступном Docker;
- `local` при отсутствии Docker.

Это позволяет запускать `make test`, `make build`, `make analyse` и `make quality` без контейнеров, если в системе установлены `php`, `composer` и `npm`.

Контейнерные команды `up`, `down`, `restart`, `shell`, `logs` остаются доступны только в режиме `sail`.
