# Деплой на Fly.io

В проект добавлены:

- [Dockerfile](../Dockerfile)
- [fly.toml](../fly.toml)
- [GitHub Actions deploy workflow](../.github/workflows/fly-deploy.yml)

## Что уже настроено

- production runtime на `nginx + php-fpm`;
- healthcheck на `GET /up`;
- release command: `php artisan migrate --force`;
- ручной деплой через GitHub Actions;
- сборка frontend-ассетов внутри Docker image.

## Предпосылки

Нужны:

- аккаунт Fly.io;
- установленный `flyctl`;
- созданное приложение Fly;
- PostgreSQL, доступный приложению через `DATABASE_URL`.

## Первый запуск

```bash
fly auth login
fly launch --copy-config --no-deploy
fly postgres create
fly postgres attach --app amopoint-test
fly secrets set APP_KEY=base64:... STATS_LOGIN=admin STATS_PASSWORD=secret STATS_JWT_SECRET=...
fly deploy
```

Если имя приложения отличается, обновите `app` и `APP_URL` в [fly.toml](../fly.toml).

## Обязательные secrets

Минимально:

```bash
fly secrets set \
  APP_KEY=base64:... \
  STATS_LOGIN=admin \
  STATS_PASSWORD=secret \
  STATS_JWT_SECRET=some-long-random-string
```

При использовании Fly Postgres после `fly postgres attach` переменная `DATABASE_URL` обычно настраивается автоматически.

## GitHub Actions deploy

Workflow `.github/workflows/fly-deploy.yml` запускается вручную через `workflow_dispatch`.

Для него нужен repository secret:

```text
FLY_API_TOKEN
```

После этого деплой можно запускать из вкладки `Actions`.

## Принятые ограничения

Текущий `fly.toml` ориентирован на один web process и обычный application deploy:

- `CACHE_STORE=database`
- `STATS_RATE_LIMIT_DRIVER=memory`
- `SESSION_DRIVER=cookie`
- `QUEUE_CONNECTION=sync`

Это хороший baseline для тестового или небольшого сервиса без Redis. Если включаете Redis, задайте реальный `REDIS_HOST`/`REDIS_URL` и можно вернуть `CACHE_STORE=redis`, `STATS_RATE_LIMIT_DRIVER=redis`.
