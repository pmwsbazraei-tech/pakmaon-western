#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Skrip deploy Laravel Forge untuk Pak Maon Western.
# Salin kandungan ini ke dalam "Deploy Script" pada panel Forge site anda.
# ---------------------------------------------------------------------------
cd $FORGE_SITE_PATH

git pull origin $FORGE_SITE_BRANCH

composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

php artisan migrate --force

npm ci && npm run build

php artisan storage:link

php artisan optimize

$FORGE_PHP artisan config:cache
$FORGE_PHP artisan route:cache
$FORGE_PHP artisan view:cache
