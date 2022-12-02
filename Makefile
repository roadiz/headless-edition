#
# Development tasks
#
include .env

GREEN=\033[0;32m
RED=\033[0;31m
# No Color
NC=\033[0m
# Use a local available port
DEV_DOMAIN="0.0.0.0:${APP_PORT}"

cache :
	php bin/roadiz cache:clear -e dev
	php bin/roadiz cache:clear -e prod
	php bin/roadiz cache:clear -e prod --preview
	php bin/roadiz cache:clear-fpm -e prod -d ${DEV_DOMAIN}
	php bin/roadiz cache:clear-fpm -e prod --preview -d ${DEV_DOMAIN}
	php bin/roadiz cache:clear-fpm -e prod
	php bin/roadiz cache:clear-fpm -e prod --preview

# Launch PHP internal server (for dev purpose only)
dev-server:
	@echo "✅\t${GREEN}Launching PHP dev server in web/ folder${NC}" >&2;
	php -S ${DEV_DOMAIN} -t web vendor/roadiz/roadiz/conf/router.php

# Migrate your app, update DB and empty caches.
migrate:
	php bin/roadiz themes:migrate src/Resources/config.yml;

build-docker:
	rm -f composer.lock
	docker-compose exec -u www-data app composer install --optimize-autoloader --apcu-autoloader;
	docker buildx build --push --platform linux/arm64/v8,linux/amd64 --tag roadiz/headless-edition:latest --tag roadiz/headless-edition:1.7.3 .
	docker buildx build --push --platform linux/arm64/v8,linux/amd64 --tag roadiz/varnish:latest --tag roadiz/varnish:1.7.3 ./docker/varnish

build-docker-latest:
	rm -f composer.lock
	docker-compose exec -u www-data app composer install --optimize-autoloader --apcu-autoloader;
	docker buildx build --push --platform linux/arm64/v8,linux/amd64 --tag roadiz/headless-edition:latest .
	docker buildx build --push --platform linux/amd64 --tag roadiz/varnish:latest ./docker/varnish

build-docker-tag:
	rm -f composer.lock
	docker-compose exec -u www-data app composer install --optimize-autoloader --apcu-autoloader;
	docker buildx build --push --platform linux/arm64/v8,linux/amd64 --tag roadiz/headless-edition:${IMAGE_TAG} .
	docker buildx build --push --platform linux/amd64 --tag roadiz/varnish:${IMAGE_TAG} ./docker/varnish

ngrok:
	ngrok http ${DEV_DOMAIN}

test:
	php vendor/bin/phpcbf -p
	php vendor/bin/phpstan analyse -c phpstan.neon

blackfire:
	docker-compose exec blackfire blackfire curl http://app
