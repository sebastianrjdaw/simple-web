#!/usr/bin/env bash
set -euo pipefail

HTTP_PORT="${SIMPLEVIEW_HTTP_PORT:-80}"
failed=0
if curl --fail --silent --show-error "http://127.0.0.1:${HTTP_PORT}/up" >/dev/null; then
  echo "OK: aplicación HTTP"
else
  echo "ERROR: aplicación HTTP"
  failed=1
fi
if docker compose exec -T app php artisan simpleview:health-check; then
  echo "OK: SQLite y persistencia"
else
  echo "ERROR: SQLite o persistencia"
  failed=1
fi
docker compose ps
exit "$failed"
