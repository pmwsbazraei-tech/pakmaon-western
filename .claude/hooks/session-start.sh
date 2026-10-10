#!/bin/bash
# Sediakan projek secara automatik bila sesi Claude Code (web/cloud) bermula:
# pasang dependency, sediakan .env + SQLite, migrate & seed, build asset.
set -euo pipefail

# Hanya jalan dalam sesi cloud (Claude Code di web). Di laptop, guna README.
if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
  exit 0
fi

cd "${CLAUDE_PROJECT_DIR:-.}"

# Container cloud ada PHP 8.3 (PHP 8.4 tak boleh dipasang sebab sekatan rangkaian),
# jadi abaikan semakan versi PHP. Production (Forge) tetap guna PHP 8.4.
composer install --no-interaction --prefer-dist --ignore-platform-req=php
npm ci --no-audit --no-fund

[ -f .env ] || cp .env.example .env
grep -q '^APP_KEY=base64' .env || php artisan key:generate --ansi

# Seed data demo hanya sekali (bila DB baru dicipta) supaya tiada data berganda.
if [ ! -f database/database.sqlite ]; then
  touch database/database.sqlite
  php artisan migrate --seed --force
else
  php artisan migrate --force
fi

npm run build
[ -L public/storage ] || php artisan storage:link
