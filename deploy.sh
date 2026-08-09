#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Skrip deploy Laravel Forge untuk Pak Maon Western.
# Salin kandungan ini ke dalam "Deploy Script" pada panel Forge site anda.
# `php artisan optimize` sudah cache config + route + view + events sekali gus.
# ---------------------------------------------------------------------------
cd $FORGE_SITE_PATH

git pull origin $FORGE_SITE_BRANCH

$FORGE_COMPOSER install --no-interaction --prefer-dist --optimize-autoloader --no-dev

( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

php artisan migrate --force

npm ci && npm run build

[ -L public/storage ] || php artisan storage:link

php artisan optimize
