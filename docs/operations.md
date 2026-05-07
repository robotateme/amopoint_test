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

## k6 smoke test статистики

Black-box сценарий `tests/k6/stats.js` проверяет полный поток статистики:

- открывает `/stats/login` и забирает CSRF token;
- логинится в статистику;
- читает baseline JSON `/stats?hours=1`;
- отправляет несколько уникальных визитов в `POST /api/visits`;
- повторно читает статистику и проверяет рост `stats.total`, наличие hourly rows и city rows.

Сценарий запускается в изолированном Docker-контейнере `grafana/k6`; локальная установка k6 не нужна.

Через Makefile:

```bash
K6_BASE_URL=http://127.0.0.1 \
STATS_LOGIN=admin \
STATS_PASSWORD=secret \
K6_VISIT_COUNT=3 \
make k6-stats
```

Предупреждение: сценарий пишет synthetic visits в `/api/visits` выбранного окружения. Используйте test/staging базу, если тестовые визиты не должны попасть в production.

Переменные:

```text
K6_IMAGE        Docker image k6, по умолчанию grafana/k6:1.3.0
K6_BASE_URL     адрес приложения, по умолчанию http://127.0.0.1
STATS_LOGIN     логин страницы статистики, по умолчанию admin
STATS_PASSWORD  пароль страницы статистики, по умолчанию secret
K6_VISIT_COUNT  количество уникальных визитов, по умолчанию 3
STATS_HOURS     окно статистики, по умолчанию 1
```

Сценарий рассчитан на smoke/regression проверку корректности агрегатов, а не на нагрузочный тест.

## k6 browser smoke test статистики

Browser-сценарий `tests/k6/stats-browser.js` проверяет пользовательское поведение в Chromium:

- открывает `/stats/login`;
- заполняет форму логина;
- проверяет, что dashboard открылся;
- проверяет наличие заголовка статистики, подписей графиков и canvas-графика;
- отправляет synthetic visit из browser context;
- перезагружает dashboard и проверяет рост основной метрики.

Запуск выполняется через Docker image с браузером:

```bash
K6_BASE_URL=http://127.0.0.1 \
STATS_LOGIN=admin \
STATS_PASSWORD=secret \
make k6-stats-browser
```

Переменные:

```text
K6_BROWSER_IMAGE     Docker image k6 browser, по умолчанию grafana/k6:latest-with-browser
K6_BROWSER_HEADLESS  headless режим Chromium, по умолчанию true
K6_BASE_URL          адрес приложения, по умолчанию http://127.0.0.1
STATS_LOGIN          логин страницы статистики, по умолчанию admin
STATS_PASSWORD       пароль страницы статистики, по умолчанию secret
```

Предупреждение: официальный k6 browser Docker image запускает Chromium с `no-sandbox`. Используйте только доверенные test/staging URL. Сценарий также пишет synthetic visits в выбранную БД.

## k6 browser + Socket.IO сценарий

Сценарий `tests/k6/stats-socket-browser.js` проверяет процесс с несколькими независимыми browser-сессиями и Socket.IO:

- каждый browser VU логинится в `/stats`;
- каждый browser VU ждет подключения dashboard-а к Socket.IO;
- каждый browser VU пишет уникальный synthetic visit из browser context;
- Laravel после записи визита отправляет событие в Socket.IO server;
- этот же browser VU проверяет, что получил socket-событие и что основная метрика выросла без reload страницы.

Запуск:

```bash
K6_BASE_URL=http://127.0.0.1 \
STATS_LOGIN=admin \
STATS_PASSWORD=secret \
K6_BROWSER_VISITORS=3 \
make k6-stats-socket-browser
```

Предусловия:

- приложение запущено;
- `SOCKET_IO_ENABLED=true`;
- Socket.IO server запущен и доступен dashboard-у;
- при запуске через `php artisan serve` dashboard-у обычно нужен прямой URL, например `SOCKET_IO_CLIENT_URL=http://127.0.0.1:6001`;
- для production-like Docker/nginx окружения `/socket.io` должен проксироваться в Socket.IO server.

Переменные:

```text
K6_BROWSER_VISITORS  количество browser VU, по умолчанию 3
K6_BROWSER_IMAGE     Docker image k6 browser, по умолчанию grafana/k6:latest-with-browser
K6_BROWSER_HEADLESS  headless режим Chromium, по умолчанию true
K6_BASE_URL          адрес приложения, по умолчанию http://127.0.0.1
STATS_LOGIN          логин страницы статистики, по умолчанию admin
STATS_PASSWORD       пароль страницы статистики, по умолчанию secret
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
