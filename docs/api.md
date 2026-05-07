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

В проекте скрипт подключен только на главной странице `/` в `resources/views/welcome.blade.php`. Поэтому автоматически считаются посещения главной страницы. `/stats`, `/stats/login`, API-роуты и другие страницы не создают запись в `visits`, пока `visit-counter.js` не подключен к соответствующему Blade-шаблону.

### Уникальность посетителей

Уникальность считается по `fingerprint`.

Фронтовый скрипт:

- читает `amopoint_visit_fingerprint` из `localStorage`;
- если значения нет, создает новое из времени, случайного значения, user-agent и размера экрана;
- сохраняет fingerprint в `localStorage`;
- отправляет тот же fingerprint при следующих визитах из этого браузера.

Если клиент не передал fingerprint, backend строит fallback:

```text
sha256(ip | user-agent | page-url)
```

Агрегаты статистики используют `count(distinct fingerprint)`. Это значит:

- обновление страницы и повторные заходы из того же браузера не увеличивают уникальный счетчик;
- другой браузер, инкогнито, очищенный `localStorage` или другое устройство считаются новым уникальным посетителем;
- `stats.total` считается отдельно глобально за выбранное окно;
- разбивки по часам и городам тоже используют `distinct fingerprint`;
- сумма городов может отличаться от `stats.total`, если один fingerprint за выбранное окно попал в разные города.

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

`POST /stats/login` защищен sliding-window rate limiting через Redis. По умолчанию:

- `5` попыток
- окно `60` секунд

## Что показывает страница статистики

- уникальные посещения по часам;
- распределение по городам;
- сводные метрики по визитам;
- агрегированную картину за заданное окно времени.

JSON-ответ `/stats?hours=...` для dashboard содержит:

```json
{
  "stats": {
    "total": 12,
    "hours": [{"hour": "2026-05-07 05:00", "visits": 12}],
    "cities": [{"city": "Paris", "visits": 10}]
  },
  "hours": 24
}
```

## Скрипт фильтрации полей

Готовый файл:

```text
public/js/type-field-filter.js
```

Скрипт находит select `Тип`, читает выбранное значение и скрывает поля, в имени которых нет выбранного типа.
