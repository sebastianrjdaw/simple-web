#!/usr/bin/env bash
set -euo pipefail
archive="${1:-}"
[[ -f "$archive" ]] || { echo "Uso: $0 /ruta/copia.tar.gz" >&2; exit 1; }
if [[ -f "${archive}.sha256" ]]; then (cd "$(dirname "$archive")" && sha256sum -c "$(basename "$archive").sha256"); fi
./scripts/backup.sh --full
tmp="$(mktemp -d)"; trap 'rm -rf "$tmp"' EXIT
tar -xzf "$archive" -C "$tmp"
[[ -f "$tmp/database.sqlite" ]] || { echo "La copia no contiene database.sqlite" >&2; exit 1; }
docker compose stop web scheduler app
cp "$tmp/database.sqlite" data/database/database.sqlite
[[ -d "$tmp/media" ]] && rsync -a "$tmp/media/" data/media/
[[ -d "$tmp/thumbnails" ]] && rsync -a "$tmp/thumbnails/" data/thumbnails/
docker compose up -d
./scripts/health-check.sh
