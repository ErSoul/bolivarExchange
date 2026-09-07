#!/bin/sh
set -e

mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
touch database/database.sqlite
chown -R www-data:www-data database storage bootstrap/cache
chmod -R ug+rwX database storage bootstrap/cache

if [ "$#" -gt 0 ]; then
	exec "$@"
fi

php artisan migrate --force
exec php-fpm --nodaemonize