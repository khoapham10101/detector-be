build:
	mkdir -p data
	cp .env.example .env
	docker-compose up -d --build
	docker-compose exec www bash -c "composer install"
	docker-compose exec www bash -c "php artisan key:generate"

up:
	
	docker-compose up -d

down:
	docker-compose down

ps:
	docker-compose ps

stop:
	docker-compose stop

start:
	docker-compose start

restart:
	docker-compose restart

php:
	docker-compose exec www bash

test:
	docker-compose exec www bash -c "/var/www/html/vendor/bin/phpunit"
