#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
docker system df
echo
read -r -p "Escribe LIMPIAR para eliminar solo caché de construcción no utilizada (nunca volúmenes): " answer
[[ "$answer" == "LIMPIAR" ]] || { echo "Cancelado."; exit 0; }
docker builder prune --filter 'until=168h' --force
mkdir -p "$ROOT/data/logs"
echo "$(date -Is) docker builder prune until=168h" >> "$ROOT/data/logs/maintenance.log"
"$ROOT/scripts/storage-report.sh"
