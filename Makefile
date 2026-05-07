DEFAULT_GOAL := help

SAIL ?= ./vendor/bin/sail
HAS_SAIL := $(shell test -x "$(SAIL)" && echo 1 || echo 0)
DOCKER_OK := $(shell docker info >/dev/null 2>&1 && echo 1 || echo 0)
RUNTIME ?= auto

ifeq ($(RUNTIME),auto)
ifneq ($(HAS_SAIL),1)
RUNTIME := local
else ifneq ($(DOCKER_OK),1)
RUNTIME := local
else
RUNTIME := sail
endif
endif

ifeq ($(RUNTIME),sail)
PHP := $(SAIL) php
COMPOSER := $(SAIL) composer
ARTISAN := $(SAIL) artisan
NPM := $(SAIL) npm
else
PHP := php
COMPOSER := composer
ARTISAN := php artisan
NPM := npm
endif

CYAN := \033[36m
GREEN := \033[32m
YELLOW := \033[33m
MAGENTA := \033[35m
RESET := \033[0m

.PHONY: help check-php setup install up down restart shell logs migrate fresh seed test stan psalm analyse format format-check build quality routes

help: ## Показать список команд
	@printf '\n$(CYAN)Amopoint Test$(RESET)\n'
	@printf '$(YELLOW)Использование:$(RESET) make <target>\n\n'
	@printf '$(YELLOW)Режим:$(RESET) $(MAGENTA)%s$(RESET)\n\n' "$(RUNTIME)"
	@awk 'BEGIN {FS = ":.*## "}; /^[a-zA-Z0-9_.-]+:.*## / {printf "  $(GREEN)%-12s$(RESET) %s\n", $$1, $$2}' $(MAKEFILE_LIST)

check-php: ## Проверить локальную версию PHP
	@if [ "$(RUNTIME)" = "local" ]; then \
		version_id="$$(php -r 'echo PHP_VERSION_ID;' 2>/dev/null || echo 0)"; \
		version="$$(php -r 'echo PHP_VERSION;' 2>/dev/null || echo unavailable)"; \
		if [ "$$version_id" -lt 80505 ]; then \
			printf '$(YELLOW)PHP 8.5.5+ required for local runtime. Current PHP: %s.$(RESET)\n' "$$version"; \
			exit 1; \
		fi; \
	fi

setup: check-php ## Подготовить окружение и собрать проект
	@if [ "$(RUNTIME)" = "sail" ]; then $(SAIL) up -d; fi
	$(COMPOSER) install
	$(NPM) install
	cp -n .env.example .env || true
	$(ARTISAN) key:generate
	$(ARTISAN) migrate
	$(NPM) run build

install: check-php ## Установить PHP и JS зависимости
	$(COMPOSER) install
	$(NPM) install

up: ## Запустить Sail-сервисы
	@if [ "$(RUNTIME)" != "sail" ]; then \
		printf '$(YELLOW)Docker/Sail недоступен. Текущий режим: $(RUNTIME).$(RESET)\n'; \
		exit 1; \
	fi
	$(SAIL) up -d

down: ## Остановить Sail-сервисы
	@if [ "$(RUNTIME)" != "sail" ]; then \
		printf '$(YELLOW)Команда down доступна только в режиме sail.$(RESET)\n'; \
		exit 1; \
	fi
	$(SAIL) down

restart: ## Перезапустить Sail-сервисы
	@if [ "$(RUNTIME)" != "sail" ]; then \
		printf '$(YELLOW)Команда restart доступна только в режиме sail.$(RESET)\n'; \
		exit 1; \
	fi
	$(SAIL) down
	$(SAIL) up -d

shell: ## Открыть shell внутри контейнера приложения
	@if [ "$(RUNTIME)" != "sail" ]; then \
		printf '$(YELLOW)Команда shell доступна только в режиме sail.$(RESET)\n'; \
		exit 1; \
	fi
	$(SAIL) shell

logs: ## Показать логи Sail
	@if [ "$(RUNTIME)" != "sail" ]; then \
		printf '$(YELLOW)Команда logs доступна только в режиме sail.$(RESET)\n'; \
		exit 1; \
	fi
	$(SAIL) logs -f

migrate: check-php ## Выполнить миграции
	$(ARTISAN) migrate

fresh: check-php ## Пересоздать схему и наполнить тестовыми данными
	$(ARTISAN) migrate:fresh --seed

seed: check-php ## Запустить сидеры
	$(ARTISAN) db:seed

test: check-php ## Запустить PHPUnit
	APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= CACHE_STORE=array SESSION_DRIVER=array QUEUE_CONNECTION=sync $(ARTISAN) test

stan: check-php ## Запустить PHPStan level 8
	$(PHP) vendor/bin/phpstan analyse --memory-limit=1G

psalm: check-php ## Запустить Psalm level 1
	$(PHP) vendor/bin/psalm --show-info=false

analyse: ## Запустить PHPStan и Psalm
	$(MAKE) stan
	$(MAKE) psalm

format: check-php ## Исправить стиль кода через Pint
	$(PHP) vendor/bin/pint

format-check: check-php ## Проверить стиль кода через Pint
	$(PHP) vendor/bin/pint --test

build: ## Собрать frontend-ассеты
	$(NPM) run build

quality: ## Прогнать форматирование, тесты, анализ и сборку
	$(MAKE) format-check
	$(MAKE) test
	$(MAKE) analyse
	$(MAKE) build

routes: check-php ## Показать список маршрутов
	$(ARTISAN) route:list
