#!/usr/bin/env bash
set -euo pipefail

command -v docker >/dev/null || { echo "Docker no está instalado." >&2; exit 1; }
command -v openssl >/dev/null || { echo "OpenSSL no está instalado." >&2; exit 1; }
docker compose version >/dev/null || { echo "Docker Compose no está disponible." >&2; exit 1; }

if [[ ! -f .env ]]; then
  cp .env.example .env
fi

if ! grep -Eq '^APP_KEY=base64:.+' .env; then
  key="base64:$(openssl rand -base64 32 | tr -d '\n')"
  sed -i "s|^APP_KEY=.*|APP_KEY=${key}|" .env
fi

mkdir -p data/{database,media,thumbnails,backups,logs,cache}
chmod -R u+rwX,g+rwX data

docker compose build
docker compose up -d

echo
echo "Simple View está listo: http://localhost/admin"
echo "Usuario: admin"
echo "Contraseña inicial: admin123"
echo "Cámbiala desde el menú de usuario > Perfil."
