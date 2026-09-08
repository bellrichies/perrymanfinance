.PHONY: up down install migrate seed test lint build api web-dev

up:
	node scripts/dev.mjs up

down:
	node scripts/dev.mjs down

install:
	composer --working-dir=backend install
	npm --prefix web ci

migrate:
	php backend/bin/migrate.php

seed:
	php backend/bin/seed.php

test:
	composer --working-dir=backend test
	npm --prefix web test -- --run

lint:
	composer --working-dir=backend lint
	npm --prefix web run lint
	npm --prefix web run typecheck

build:
	npm --prefix web run build

api:
	php -S 127.0.0.1:8090 -t backend/public

web-dev:
	npm --prefix web run dev
