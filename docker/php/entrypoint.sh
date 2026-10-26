#!/bin/sh
set -e

cd /var/www/backend

if [ -f composer.json ]; then
  composer install --no-interaction --prefer-dist
fi

for i in $(seq 1 60); do
  if php bin/console doctrine:query:sql "SELECT 1" >/dev/null 2>&1; then
    break
  fi
  echo "Ожидаем MySQL… ($i/60)"
  sleep 2
done

php bin/console doctrine:migrations:migrate --no-interaction

exec docker-php-entrypoint "$@"
