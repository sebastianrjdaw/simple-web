#!/usr/bin/env bash
set -euo pipefail
target="${1:-$(cat .last-deployed-commit 2>/dev/null || true)}"
[[ -n "$target" ]] || { echo "Indica un commit o tag." >&2; exit 1; }
echo "Aviso: las migraciones de base de datos no se revierten automáticamente."
git checkout --detach "$target"
docker compose build
docker compose up -d
./scripts/health-check.sh
