# Архитектура проекта

## Структура

- `src/Domain` — доменные сущности, value objects и порты.
- `src/Application` — команды, запросы, обработчики и application services.
- `src/Application/Persistence` — Criteria-объекты для репозиториев.
- `src/Infrastructure` — API-клиенты, Eloquent-репозитории, cache-адаптеры, persistence mapping.
- `app/Http` — контроллеры и middleware Laravel.
- `app/Models` — Eloquent-модели.
- `app/Console` — Artisan-команды.
- `app/Providers` — DI и привязки реализаций.
- `config/persistence.php` — alias -> model mapping.
- `routes/web.php` — web routes.
- `routes/api.php` — JSON API routes.
- `resources/js` — Vue dashboard и ECharts-компоненты.
- `public/js` — подключаемые browser-only скрипты.

## Принципы

- `Domain` и `Application` не зависят от Laravel UI-слоя.
- `Infrastructure` не зависит напрямую от `app/Models`, а использует mapping через конфигурацию persistence.
- сценарии разделены по `CQRS`: `Command` и `Query` живут отдельно;
- модели преобразуются в доменные объекты через mapper-слой;
- Eloquent `casts()` не используются как часть доменной модели.

## Persistence

В проекте используется маппинг alias -> Eloquent model через `config/persistence.php`. Репозитории получают модель через resolver и работают через criteria context.

Это позволяет:

- не тянуть `App\\Models` в infrastructure-код напрямую;
- держать маппинг явно в конфигурации;
- изолировать правила выборки и маппинга.

## Frontend

Страница статистики монтируется из `resources/js/app.js` и lazy-load-ит `resources/js/pages/StatsDashboard.vue`.

Графики строятся на ECharts. Dashboard оформлен как отдельный Vue-экран, а login и shell-страницы рендерятся через Blade.
