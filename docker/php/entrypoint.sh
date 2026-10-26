#!/bin/sh
set -e

cd /var/www/backend

if [ -f composer.json ]; then
  composer install --no-interaction --prefer-dist
fi

exec docker-php-entrypoint "$@"
