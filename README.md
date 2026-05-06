# Тестовое задание Amopoint

Laravel 13 / PHP 8.3+ приложение для тестового задания PHP-разработчика. В проекте реализованы импорт шуток по расписанию, JSON API, подключаемый браузерный скрипт фильтрации полей, счетчик посещений и защищенная страница статистики.

## Стек

- Laravel 13, PHP 8.3+
- PostgreSQL 18 и Redis через Laravel Sail
- Vue 3, Vite 8 и ECharts 6 для страницы статистики
- PHPUnit 12, SQLite `:memory:` для быстрых автоматических тестов

## Структура

Бизнес-логика отделена от Laravel-адаптеров:

- `src/Domain`: сущности и порты доменной логики.
- `src/Application`: команды, запросы и обработчики сценариев.
- `src/Application/Persistence`: Criteria-объекты для репозиториев.
- `src/Infrastructure`: API-клиенты, Eloquent-репозитории, model mapping и адаптер кеша.
- `app/Http`: контроллеры и middleware.
- `app/Models`: Eloquent-модели Laravel.
- `app/Console`: Artisan-команды.
- `app/Providers`: DI-привязки интерфейсов к реализациям.
- `config/persistence.php`: маппинг persistence alias на Eloquent-модели.
- `routes/web.php`: web-страницы `/` и `/stats`.
- `routes/api.php`: JSON/CORS endpoints с префиксом `/api`.
- `resources/js`: Vue dashboard и ECharts-компоненты.
- `public/js`: подключаемые browser-only скрипты.

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

Стандартные порты сервисов задаются в `.env.example`:

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

Доступ к странице статистики задается в `.env`. Авторизация для `/stats` использует JWT в HttpOnly cookie или Bearer token:

```dotenv
STATS_LOGIN=admin
STATS_PASSWORD=secret
STATS_JWT_SECRET=
STATS_JWT_TTL=3600
```

## Локальный запуск без Sail

Подходит, если локально уже установлены PHP, Composer, Node.js, PostgreSQL и Redis.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run dev
php artisan serve
```

В этом режиме проверьте, что `DB_HOST`, `DB_PORT`, `REDIS_HOST` и `REDIS_PORT` указывают на локальные сервисы.

## Импорт шуток

Консольная команда получает случайную шутку из Official Joke API и сохраняет ее в таблицу `jokes`:

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

JSON endpoint возвращает последние сохраненные шутки:

```text
GET /api/jokes
GET /api/jokes?limit=100
```

Параметр `limit` ограничивается диапазоном `1..200`, значение по умолчанию - `50`.

## Тестовые данные

Сидер `Database\Seeders\VisitSeeder` создает тестовые просмотры за последние 24 часа для страницы статистики:

```bash
./vendor/bin/sail artisan db:seed --class=VisitSeeder
```

Общий сидер также подключает эти данные:

```bash
./vendor/bin/sail artisan db:seed
```

Сидер просмотров идемпотентен для своих данных: перед вставкой он удаляет только записи с fingerprint `seed-visitor-*`.

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
OPTIONS /api/visits
GET /stats
GET /stats?hours=48
```

`POST /api/visits` возвращает `201` и JSON `{"ok": true}`. Для внешнего подключения скрипта endpoint отдает CORS-заголовки на `POST` и `OPTIONS`.

`/stats` защищен JWT auth и рендерит Vue 3 dashboard. Для браузерного входа используется форма:

```text
GET /stats/login
POST /stats/login
POST /stats/logout
```

- уникальные посещения по часам;
- распределение по городам;
- сводные метрики: всего уникальных посещений, час пик и топ город.

Параметр `/stats?hours=` ограничивается диапазоном `1..168`, значение по умолчанию - `24`.

## Redis cache

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

ECharts выбран вместо CDN-скрипта Chart.js, потому что дает интерактивные графики, tooltip, адаптивный canvas-rendering и нормальную сборку через Vite.

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

Без Sail:

```bash
composer test
npm run build
./vendor/bin/pint --dirty
```

Тесты покрывают JSON endpoint шуток, запись визита и авторизацию/данные монтирования страницы статистики.
