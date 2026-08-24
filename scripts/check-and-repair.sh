#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
REPAIR=true
JSON=false

usage() {
    echo "Uso: ./scripts/check-and-repair.sh [--check-only] [--json]"
}

while (($#)); do
    case "$1" in
        --check-only) REPAIR=false ;;
        --json) JSON=true ;;
        -h|--help) usage; exit 0 ;;
        *) echo "Opción desconocida: $1" >&2; usage; exit 2 ;;
    esac
    shift
done

command -v docker >/dev/null 2>&1 || { echo "ERROR: Docker no está instalado." >&2; exit 1; }
root_prefix=()
docker_command=(docker)
if ((EUID != 0)) && ! docker info >/dev/null 2>&1; then
    command -v sudo >/dev/null 2>&1 || { echo "ERROR: Docker requiere root y sudo no está instalado." >&2; exit 1; }
    sudo -v
    root_prefix=(sudo)
    docker_command=(sudo docker)
fi
"${docker_command[@]}" compose version >/dev/null
"${docker_command[@]}" compose config --quiet

if [[ "$REPAIR" == true ]]; then
    echo "Preparando servicios y almacenamiento persistente..."
    if ((EUID != 0)) && ((${#root_prefix[@]} == 0)) && command -v sudo >/dev/null 2>&1; then
        sudo -v
        root_prefix=(sudo)
    fi
    if ((EUID == 0)) || ((${#root_prefix[@]})); then
        if command -v systemctl >/dev/null 2>&1; then
            "${root_prefix[@]}" systemctl enable --now docker
            "${root_prefix[@]}" systemctl enable expositor-remoto.service 2>/dev/null || true
            "${root_prefix[@]}" systemctl enable --now simple-view-storage-report.timer 2>/dev/null || true
        fi
        if id display >/dev/null 2>&1; then
            display_home="$(getent passwd display | cut -d: -f6)"
            display_group="$(id -gn display)"
            "${root_prefix[@]}" install -d -o display -g "$display_group" -m 0755 \
                "$display_home/.config" "$display_home/.config/autostart" \
                "$display_home/Desktop" "$display_home/Escritorio"
            "${root_prefix[@]}" install -d -o display -g "$display_group" -m 0700 \
                "$display_home/.config/dconf"
            "${root_prefix[@]}" chown -R "display:$display_group" "$display_home/.config"
        fi
    fi
    "${docker_command[@]}" compose up -d --remove-orphans
    if ((EUID == 0)); then
        ./scripts/storage-report.sh
    elif ((${#root_prefix[@]})); then
        sudo ./scripts/storage-report.sh
    else
        echo "AVISO: no se actualizó la métrica del host porque sudo requiere autenticación."
    fi
fi

args=(simpleview:doctor)
[[ "$REPAIR" == true ]] && args+=(--repair)
[[ "$JSON" == true ]] && args+=(--json)
doctor_status=0
"${docker_command[@]}" compose exec -T app php artisan "${args[@]}" || doctor_status=$?

failed=0
for unit in docker expositor-remoto simple-view-storage-report.timer; do
    if command -v systemctl >/dev/null 2>&1 && systemctl list-unit-files "$unit" --no-legend 2>/dev/null | grep -q "$unit"; then
        if ! systemctl is-active --quiet "$unit"; then
            echo "ERROR: la unidad $unit no está activa." >&2
            failed=1
        fi
    fi
done
for service in app web worker scheduler; do
    if ! "${docker_command[@]}" compose ps --status running --services | grep -qx "$service"; then
        echo "ERROR: el servicio $service no está ejecutándose." >&2
        failed=1
    fi
done

http_port="$(sed -n 's/^SIMPLEVIEW_HTTP_PORT=//p' .env | tail -n1 | tr -d '[:space:]\"')"
http_port="${http_port:-80}"
if curl --fail --silent --show-error --max-time 10 "http://127.0.0.1:${http_port}/up" >/dev/null; then
    [[ "$JSON" == true ]] || echo "OK: servidor HTTP disponible en el puerto $http_port."
else
    echo "ERROR: el servidor HTTP no responde." >&2
    failed=1
fi

"${docker_command[@]}" compose ps
((doctor_status == 0 && failed == 0))
