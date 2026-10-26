#!/bin/sh
set -e

cd /var/www/backend

if [ -f composer.json ]; then
  composer install --no-interaction --prefer-dist
fi

ready=0
for i in $(seq 1 60); do
  if php bin/console dbal:run-sql "SELECT 1" --no-interaction --quiet >/dev/null 2>&1; then
    ready=1
    break
  fi
  echo "Ожидаем MySQL… ($i/60)"
  sleep 2
done

if [ "$ready" -ne 1 ]; then
  echo "MySQL недоступен после 60 попыток" >&2
  exit 1
fi

php bin/console doctrine:migrations:migrate --no-interaction

php bin/ensure-test-database.php

php bin/console doctrine:database:create --if-not-exists --env=test --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction --env=test

exec docker-php-entrypoint "$@"
