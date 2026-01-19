#!/usr/bin/env bash

cp .env.example .env

docker-compose up -d


docker compose exec php composer install
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php php bin/console doctrine:database:create --env=test
docker compose exec php php bin/console doctrine:migrations:migrate --env=test --no-interaction

docker compose exec php php bin/phpunit tests/Unit
docker compose exec php php bin/phpunit tests/Functional