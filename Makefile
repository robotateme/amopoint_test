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

.PHONY: help setup install up down restart shell logs migrate fresh seed test stan psalm analyse format format-check build quality routes

help: ## Показать список команд
	@printf '\n$(CYAN)Amopoint Test$(RESET)\n'
	@printf '$(YELLOW)Использование:$(RESET) make <target>\n\n'
	@printf '$(YELLOW)Режим:$(RESET) $(MAGENTA)%s$(RESET)\n\n' "$(RUNTIME)"
	@awk 'BEGIN {FS = ":.*## "}; /^[a-zA-Z0-9_.-]+:.*## / {printf "  $(GREEN)%-12s$(RESET) %s\n", $$1, $$2}' $(MAKEFILE_LIST)

setup: ## Подготовить окружение и собрать проект
	composer install
	npm install
	cp -n .env.example .env || true
	php artisan key:generate
	@if [ "$(RUNTIME)" = "sail" ]; then $(SAIL) up -d; fi
	$(ARTISAN) migrate
	$(NPM) run build

install: ## Установить PHP и JS зависимости
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

migrate: ## Выполнить миграции
	$(ARTISAN) migrate

fresh: ## Пересоздать схему и наполнить тестовыми данными
	$(ARTISAN) migrate:fresh --seed

seed: ## Запустить сидеры
	$(ARTISAN) db:seed

test: ## Запустить PHPUnit
	$(ARTISAN) test

stan: ## Запустить PHPStan level 8
	$(PHP) vendor/bin/phpstan analyse --memory-limit=1G

psalm: ## Запустить Psalm level 1
	$(PHP) vendor/bin/psalm --show-info=false

analyse: ## Запустить PHPStan и Psalm
	$(MAKE) stan
	$(MAKE) psalm

format: ## Исправить стиль кода через Pint
	$(PHP) vendor/bin/pint

format-check: ## Проверить стиль кода через Pint
	$(PHP) vendor/bin/pint --test

build: ## Собрать frontend-ассеты
	$(NPM) run build

quality: ## Прогнать форматирование, тесты, анализ и сборку
	$(MAKE) format-check
	$(MAKE) test
	$(MAKE) analyse
	$(MAKE) build

routes: ## Показать список маршрутов
	$(ARTISAN) route:list
