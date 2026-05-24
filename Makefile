ifndef APP_ENV
    include .env
endif

.DEFAULT_GOAL := help
.PHONY: help
help:
    @awk 'BEGIN {FS = ":.*?## "}; /^[a-zA-Z-]+:.*?## .*$$/ {printf "[32m%-15s[0m %s\n", $$1, $$2}' Makefile | sort

###> shopware/docker-dev ###
up:
	@touch .env.local
	docker compose up -d
stop:
	docker compose stop
down:
	docker compose down
shell:
	docker compose exec web bash
watch-storefront:
	docker compose exec -e PROXY_URL=http://localhost:9998 web ./bin/watch-storefront.sh
watch-admin:
	docker compose exec web ./bin/watch-administration.sh
build-storefront:
	docker compose exec web ./bin/build-storefront.sh
build-administration:
	docker compose exec web ./bin/build-administration.sh
setup:
	docker compose exec web composer install
	docker compose exec web bin/console system:install --basic-setup --create-database --drop-database --force
###< shopware/docker-dev ###
