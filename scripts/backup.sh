#!/usr/bin/env bash
set -euo pipefail
if [[ "${1:-}" == "--full" ]]; then docker compose exec -T app php artisan simpleview:backup --full; else docker compose exec -T app php artisan simpleview:backup; fi
