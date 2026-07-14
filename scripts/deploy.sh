#!/usr/bin/env bash
set -euo pipefail
git diff --quiet && git diff --cached --quiet || { echo "Hay cambios locales sin confirmar." >&2; exit 1; }
previous="$(git rev-parse HEAD)"; echo "$previous" > .last-deployed-commit
git pull --ff-only
if ! docker compose build || ! docker compose up -d || ! ./scripts/health-check.sh; then
  echo "El despliegue falló; restaurando $previous" >&2
  git checkout --detach "$previous"; docker compose build; docker compose up -d
  exit 1
fi
echo "$(date -Is) $(git rev-parse HEAD)" >> data/logs/deployments.log
