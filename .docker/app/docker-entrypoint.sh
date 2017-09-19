#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
    set -- php-fpm "$@"
fi

if [ "$1" = 'php-fpm' ]; then
    # The first time volumes are mounted, dependencies need to be reinstalled
    if [ ! -f composer.json ]; then
        rm -Rf vendor/*
        composer install --prefer-dist --no-progress --no-suggest --no-interaction
    fi

    # Permissions hack because setfacl does not work on Mac and Windows
    chown -R www-data var
fi

echo "xdebug.remote_host=$(/sbin/ip route|awk '/default/ { print $3 }')" >> /usr/local/etc/php/php.ini

exec docker-php-entrypoint "$@"
