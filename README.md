# Тестовое задание Amopoint

Laravel 13 / PHP 8.5 приложение с импортом шуток по расписанию, JSON API, счетчиком посещений, JWT-защитой страницы статистики и Vue dashboard.

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
