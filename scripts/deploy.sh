#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"; cd "$ROOT"
mkdir -p data/logs
min_gb="${SIMPLEVIEW_DEPLOY_MIN_FREE_GB:-20}"; free_bytes=$(df -B1 --output=avail data | tail -n1); required_bytes=$((min_gb*1024*1024*1024))
if (( free_bytes < required_bytes )); then echo "Despliegue cancelado: se requieren ${min_gb} GB libres y solo hay $((free_bytes/1024/1024/1024)) GB." | tee -a data/logs/deployments.log >&2; exit 1; fi
echo "$(date -Is) preflight correcto: $((free_bytes/1024/1024/1024)) GB libres" >> data/logs/deployments.log
./scripts/storage-report.sh || true
git diff --quiet && git diff --cached --quiet || { echo "Hay cambios locales sin confirmar." >&2; exit 1; }
previous="$(git rev-parse HEAD)"; echo "$previous" > .last-deployed-commit
git pull --ff-only
if ! docker compose build || ! docker compose up -d || ! ./scripts/health-check.sh; then
  echo "El despliegue falló; restaurando $previous" >&2
  git checkout --detach "$previous"; docker compose build; docker compose up -d
  exit 1
fi
echo "$(date -Is) $(git rev-parse HEAD)" >> data/logs/deployments.log
