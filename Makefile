SAIL ?= ./vendor/bin/sail
PHP := $(SAIL) php
COMPOSER := $(SAIL) composer
ARTISAN := $(SAIL) artisan
NPM := $(SAIL) npm

.PHONY: help install up down shell migrate seed test stan psalm analyse lint format build quality routes

help:
	@printf '%s\n' \
		'install  Install PHP and JS dependencies' \
		'up       Start Sail services' \
		'down     Stop Sail services' \
		'migrate  Run database migrations' \
		'seed     Seed demo data' \
		'test     Run PHPUnit' \
		'stan     Run PHPStan level 8' \
		'psalm    Run Psalm level 1' \
		'analyse  Run PHPStan and Psalm' \
		'format   Run Pint' \
		'build    Build frontend assets' \
		'quality  Run format, tests, static analysis, and frontend build'

install:
	composer install
	npm install

up:
	$(SAIL) up -d

down:
	$(SAIL) down

shell:
	$(SAIL) shell

migrate:
	$(ARTISAN) migrate

seed:
	$(ARTISAN) db:seed

test:
	$(ARTISAN) test

stan:
	$(PHP) vendor/bin/phpstan analyse --memory-limit=1G

psalm:
	$(PHP) vendor/bin/psalm --show-info=false

analyse: stan psalm

format:
	$(PHP) vendor/bin/pint --dirty

build:
	$(NPM) run build

quality: format test analyse build

routes:
	$(ARTISAN) route:list
