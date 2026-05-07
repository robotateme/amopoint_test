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
| Выложить на хостинг | выполнено | Fly.io: <https://amopoint-test.fly.dev/>. Деплой оплачен free-credit периодом Fly.io, доступен 6 дней |

Примечание по деплою: на free-credit окружении Fly.io машины приложения и PostgreSQL могут останавливаться при простое. Первый запрос после холодного старта может кратко получить ошибку соединения с БД; повторный запрос проходит после запуска PostgreSQL.

## Уникальность посещений

Счетчик считает уникальных посетителей по `fingerprint`, а не общее число просмотров страниц. `public/js/visit-counter.js` хранит fingerprint в `localStorage` браузера под ключом `amopoint_visit_fingerprint` и отправляет его в `POST /api/visits`.

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
- Добавлены сиды просмотров, feature/integration tests, unit tests, PHPStan level 8, Psalm level 1, Makefile и GitHub CI.

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
make quality
```

В репозитории настроен GitHub Actions workflow `CI`, который на `push` в `main` и на `pull_request` прогоняет `make RUNTIME=local quality`.

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
