# Тестовое задание Amopoint

Laravel 13 / PHP 8.5 приложение с импортом шуток по расписанию, JSON API, счетчиком посещений, JWT-защитой страницы статистики и Vue dashboard.

## Сравнение с ТЗ

| Пункт ТЗ | Статус | Где реализовано |
|---|---:|---|
| Laravel-проект | выполнено | Laravel 13 / PHP 8.5 |
| Консольная команда получает данные из внешнего API | выполнено | `jokes:fetch`, Official Joke API |
| Команда сохраняет данные в таблицу БД | выполнено | таблица `jokes`, репозиторий шуток |
| Запуск каждые 5 минут | выполнено | `routes/console.php` |
| Route отдает массив записей JSON | выполнено | `GET /api/jokes` |
| JS-файл для страницы `testlist.html` | выполнено | `public/js/type-field-filter.js` |
| Поля фильтруются по выбранному `Тип` и `name` | выполнено | нативный DOM API, без сторонних библиотек |
| Счетчик посещений как подключаемый JS | выполнено | `public/js/visit-counter.js` |
| JS собирает устройство | выполнено | `desktop`, `mobile`, `tablet` по user-agent |
| IP и город | выполнено | JS отправляет визит, IP берется backend-ом из HTTP-запроса как доверенный источник, город определяется через `ip-api.com` |
| Backend хранит посещения в БД | выполнено | таблица `visits` |
| График уникальных посещений по часам | выполнено | `/stats`, Vue + ECharts |
| Круговая диаграмма по городам | выполнено | `/stats`, Vue + ECharts |
| Страница статистики с авторизацией | выполнено | `/stats/login`, JWT |
| Выложить на хостинг | подготовлено | Docker/Fly.io конфиг и Laravel Cloud deploy hook workflow. Фактический деплой требует активный hosting account и secrets |

Примечание по деплою: Fly.io деплой был заблокирован завершенным trial аккаунта. Для Laravel Cloud подготовлены [инструкции](docs/laravel-cloud.md) и GitHub deploy hook workflow.

## Уникальность посещений

Счетчик считает уникальных посетителей по `fingerprint`, а не общее число просмотров страниц. `public/js/visit-counter.js` хранит fingerprint в `localStorage` браузера под ключом `amopoint_visit_fingerprint` и отправляет его в `POST /api/visits`.

В текущей реализации скрипт подключен только на главной странице `/` через `resources/views/welcome.blade.php`. Страницы `/stats`, `/stats/login`, API-роуты и любые другие страницы не пишут визиты, пока в их Blade-шаблон явно не добавлен `visit-counter.js`.

Если fingerprint не пришел, backend строит fallback из IP, user-agent и URL страницы. В статистике используется `count(distinct fingerprint)`: повторные заходы из того же браузера не увеличивают уникальный счетчик, а другой браузер, инкогнито, очищенный `localStorage` или другое устройство создают нового уникального посетителя.

Общий показатель `stats.total` считается отдельно глобально за выбранное окно, а не суммой городов. Это важно, если один fingerprint попал в несколько городов: общий счетчик останется уникальным, а городовая разбивка покажет распределение по городам.

## Дополнительно

- Роуты разделены на `routes/api.php` и `routes/web.php`.
- Сценарии приложения разделены по CQRS: `Command` и `Query`.
- Eloquent-модели находятся в `app/Models`, доменный слой не зависит от Laravel-моделей.
- Доменные value objects создаются явно в mapper-слое, Eloquent `casts()` не используются для доменной конвертации.
- Репозитории используют Criteria для поиска, сортировки и лимитов.
- `/stats/login` защищен Redis sliding-window rate limiting через Lua script resolver.
- Статистика кешируется через Laravel cache с version-key invalidation.
- Для Docker/nginx окружения добавлен Socket.IO broadcaster статистики; по умолчанию `SOCKET_IO_ENABLED=false`, чтобы Laravel Cloud работал через polling fallback.
- Добавлены сиды просмотров, feature/integration tests, unit tests, k6 smoke test статистики, PHPStan level 8, Psalm level 1, Makefile и GitHub CI.

## Быстрый старт

```bash
make setup
make up
make seed
```

После запуска:

- приложение: `http://127.0.0.1`
- Vite dev server: `http://127.0.0.1:5173`
- страница статистики: `http://127.0.0.1/stats`

## Основные команды

```bash
make help
make up
make test
make k6-stats
make k6-stats-browser
make k6-stats-socket-browser
make quality
```

## k6 smoke test статистики

k6 не ставится в проект и не требуется локально: сценарий запускается из изолированного Docker-контейнера `grafana/k6`.

```bash
K6_BASE_URL=http://127.0.0.1 \
STATS_LOGIN=admin \
STATS_PASSWORD=secret \
K6_VISIT_COUNT=3 \
make k6-stats
```

Предупреждение: сценарий пишет synthetic visits в `/api/visits` выбранного окружения и проверяет рост `/stats?hours=1`. Запускайте его на test/staging базе, если не хотите добавлять тестовые визиты в production.

Сценарий является smoke/regression проверкой корректности агрегатов, а не нагрузочным тестом.

Для проверки поведения в реальном браузере есть отдельный k6 browser сценарий. Он запускает Chromium в Docker, логинится в `/stats`, проверяет dashboard и canvas-графики, создает synthetic visit из browser context и проверяет обновление метрики после reload.

```bash
K6_BASE_URL=http://127.0.0.1 \
STATS_LOGIN=admin \
STATS_PASSWORD=secret \
make k6-stats-browser
```

Предупреждение: browser image k6 запускает Chromium с `no-sandbox`, поэтому используйте только доверенные test/staging URL. Сценарий также пишет synthetic visits в выбранное окружение.

Для проверки процесса с несколькими браузерами и Socket.IO есть отдельный сценарий. Он запускает несколько независимых Chromium VU, каждый логинится в `/stats`, ждет подключения к Socket.IO, создает уникальный synthetic visit из browser context и проверяет, что этот же браузер получил socket-событие и увидел рост метрики без reload.

```bash
K6_BASE_URL=http://127.0.0.1 \
STATS_LOGIN=admin \
STATS_PASSWORD=secret \
K6_BROWSER_VISITORS=3 \
make k6-stats-socket-browser
```

Предусловие: приложение должно быть запущено с `SOCKET_IO_ENABLED=true`, а Socket.IO сервер должен быть доступен dashboard-у. При запуске через `php artisan serve` обычно нужно указать прямой client URL, например `SOCKET_IO_CLIENT_URL=http://127.0.0.1:6001`.

Если сценарий пишет `Socket.IO is disabled by the application`, тестируемое окружение отдает `SOCKET_IO_ENABLED=false`: включите `SOCKET_IO_ENABLED=true` в env приложения и redeploy/restart, чтобы Laravel перечитал конфиг. Если сценарий падает на ожидании connect, проверьте диагностические поля в ошибке k6: для `url=same-origin` нужен nginx/proxy на `/socket.io`, а без proxy задайте явный `SOCKET_IO_CLIENT_URL`.

В репозитории настроен GitHub Actions workflow `CI`, который на `push` в `main` и на `pull_request` прогоняет `make RUNTIME=local quality`.

Для Laravel Cloud автодеплоя есть workflow `.github/workflows/laravel-cloud-deploy.yml`. Ему нужен repository secret `LARAVEL_CLOUD_DEPLOY_HOOK`.

`Makefile` автоматически выбирает режим:

- `sail`, если доступен Docker и `./vendor/bin/sail`;
- `local`, если Docker/Podman не запущен или Sail недоступен.

Режим можно задать явно:

```bash
make RUNTIME=local test
make RUNTIME=sail quality
```

## Документация

- [Обзор документации](docs/README.md)
- [Локальный запуск и окружение](docs/setup.md)
- [API и сценарии использования](docs/api.md)
- [Архитектура проекта](docs/architecture.md)
- [Деплой на Fly.io](docs/deploy.md)
- [Деплой на Laravel Cloud](docs/laravel-cloud.md)
- [Эксплуатация и quality gates](docs/operations.md)
- [RFC: Architecture and Quality Gates](docs/rfc/0001-architecture-and-quality-gates.md)

## Стек

- Laravel 13
- PHP 8.5
- PostgreSQL 18
- Redis
- Vue 3
- Vite 8
- ECharts 6
- PHPUnit 12
- PHPStan level 8
- Psalm level 1
- Redis sliding-window rate limiting для `/stats/login`
