# API и сценарии использования

## Импорт шуток

Команда получает случайную шутку из Official Joke API и сохраняет ее в таблицу `jokes`.

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

## Endpoints шуток

```text
GET /api/jokes
GET /api/jokes?limit=100
```

`limit` ограничен диапазоном `1..200`, значение по умолчанию — `50`.

## Счетчик посещений

Подключаемый скрипт:

```html
<script src="https://your-domain.test/js/visit-counter.js"
        data-endpoint="https://your-domain.test/api/visits"></script>
```

Скрипт отправляет fingerprint браузера, URL страницы, referrer и тип устройства. Backend берет IP из запроса и определяет город через `ip-api.com`. Локальные адреса сохраняются как `Local`.

Маршруты:

```text
POST /api/visits
OPTIONS /api/visits
GET /stats
GET /stats?hours=48
```

`POST /api/visits` возвращает `201` и JSON `{"ok": true}`. Для внешнего подключения endpoint отдает CORS-заголовки на `POST` и `OPTIONS`.

`/stats?hours=` ограничен диапазоном `1..168`, значение по умолчанию — `24`.

## Авторизация статистики

Для браузера доступны:

```text
GET /stats/login
POST /stats/login
POST /stats/logout
```

Доступ выполняется по JWT:

- `HttpOnly` cookie для обычного браузерного сценария;
- Bearer token для API-клиентов и ручных запросов.

## Что показывает страница статистики

- уникальные посещения по часам;
- распределение по городам;
- сводные метрики по визитам;
- агрегированную картину за заданное окно времени.

## Скрипт фильтрации полей

Готовый файл:

```text
public/js/type-field-filter.js
```

Скрипт находит select `Тип`, читает выбранное значение и скрывает поля, в имени которых нет выбранного типа.
