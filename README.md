# Тестовое задание Amopoint

Laravel 13 / PHP 8.3 приложение для тестового задания PHP-разработчика. В проекте есть импорт данных по расписанию, JSON-маршрут, браузерный скрипт фильтрации полей и счетчик посещений с авторизованной страницей статистики.

## Стек

- Laravel 13, PHP 8.3+
- PostgreSQL и Redis через Laravel Sail
- Vue 3 и Vite для страницы статистики
- ECharts для интерактивных графиков
- SQLite `:memory:` для быстрых автоматических тестов

## Архитектура

Бизнес-логика отделена от Laravel-адаптеров:

- `src/Domain`: сущности, DTO и порты.
- `src/Application`: CQRS-команды, запросы и обработчики.
- `src/Infrastructure`: API-клиенты, Eloquent-репозитории и адаптеры кеша.
- `app/Http`: контроллеры и middleware.
- `app/Console`: Artisan-команды.
- `app/Providers`: DI-привязки.

Laravel-слой зависит от `src`, при этом application/domain код не зависит от контроллеров.

## Локальный запуск через Sail

```bash
composer install
cp .env.example .env
php artisan key:generate
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev -- --host 0.0.0.0
```

Стандартные порты сервисов:

```text
Приложение: http://127.0.0.1
Vite:       http://127.0.0.1:5173
PostgreSQL: 127.0.0.1:5432
Redis:      127.0.0.1:6379
```

Доступ к странице статистики задается в `.env`:

```dotenv
STATS_LOGIN=admin
STATS_PASSWORD=secret
```

## Обязательный импорт из API

Консольная команда получает случайную шутку из Official Joke API и сохраняет ее в PostgreSQL:

```bash
./vendor/bin/sail artisan jokes:fetch
```

Расписание объявлено в `routes/console.php`:

```php
Schedule::command('jokes:fetch')->everyFiveMinutes();
```

Cron для production:

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

JSON-маршрут:

```text
GET /api/jokes
GET /api/jokes?limit=100
```

## Счетчик посещений

Клиентский скрипт:

```html
<script src="https://your-domain.test/js/visit-counter.js"
        data-endpoint="https://your-domain.test/api/visits"></script>
```

Скрипт отправляет стабильный fingerprint браузера, URL страницы, referrer и тип устройства. Backend берет IP из запроса и определяет город через `ip-api.com`; локальные адреса сохраняются как `Local`.

Маршруты:

```text
POST /api/visits
GET /stats
```

`/stats` защищен HTTP Basic auth и рендерит Vue 3 dashboard:

- уникальные посещения по часам;
- распределение по городам;
- сводные метрики: всего уникальных посещений, час пик и топ город.

## Использование Redis

Redis используется как Laravel cache store:

```dotenv
CACHE_STORE=redis
REDIS_HOST=redis
```

Страница статистики кеширует агрегированные данные посещений на одну минуту через `Domain\Visit\VisitStatisticsCache`. При записи нового визита увеличивается version key, поэтому старые данные статистики инвалидируются без очистки всего кеша приложения.

Проверка ключей:

```bash
./vendor/bin/sail redis -n 1 KEYS '*visit-statistics*'
```

## Frontend

Страница статистики монтируется из `resources/js/app.js` и lazy-load-ит `resources/js/pages/StatsDashboard.vue`, поэтому ECharts загружается только на `/stats`.

Команды сборки:

```bash
./vendor/bin/sail npm run dev -- --host 0.0.0.0
./vendor/bin/sail npm run build
```

ECharts выбран вместо CDN-скрипта Chart.js, потому что дает более выразительные интерактивные графики, качественные tooltip, адаптивный canvas-rendering и нормальную сборку через Vite.

## Скрипт фильтрации полей

Готовое решение для браузера:

```text
public/js/type-field-filter.js
```

Скрипт находит select `Тип`, читает выбранное значение и скрывает каждое поле с атрибутом `name`, в котором нет выбранного значения. Его можно подключить к странице или вставить в DevTools console.

Почему выбран такой алгоритм:

- не требует менять исходную разметку страницы;
- работает от динамических значений option, а не от жестко заданного списка полей;
- скрывает ближайшую визуальную обертку поля, поэтому label и строки таблицы исчезают вместе с input.

Альтернативы, которые не выбраны:

- жесткая карта `type => field names`: быстро для одной статичной формы, но ломается при переименовании полей;
- server-side rendering: не подходит, так как нужен подключаемый файл или snippet для существующей страницы;
- jQuery-селекторы: лишняя зависимость для небольшой DOM-задачи.

## Проверки качества

```bash
./vendor/bin/sail test
./vendor/bin/sail npm run build
./vendor/bin/pint --dirty
```

Тесты покрывают JSON endpoint шуток, запись визита и авторизацию/данные монтирования страницы статистики.
