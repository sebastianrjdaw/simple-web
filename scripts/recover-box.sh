#!/usr/bin/env bash
set -Eeuo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

TARGET_BRANCH="${SIMPLEVIEW_RECOVERY_BRANCH:-main}"
TARGET_REMOTE="${SIMPLEVIEW_RECOVERY_REMOTE:-origin}"
KIOSK_URL="${SIMPLEVIEW_KIOSK_URL:-}"
HTTP_BASE_URL=""
UPDATE_CODE=true
CONFIGURE_KIOSK=true
KIOSK_READY=false
LOG_FILE="${TMPDIR:-/tmp}/simple-view-box-recovery-$(date +%Y%m%d-%H%M%S).log"
DATA_DIR="$ROOT/data"

usage() {
    cat <<'EOF'
Uso: ./scripts/recover-box.sh [opciones]

Reconstruye Simple View sin borrar .env, la base SQLite ni los archivos multimedia.

Opciones:
  --no-update     No ejecuta git fetch/pull; recupera el código actual.
  --skip-kiosk    No instala ni verifica el inicio automático en modo kiosco.
  --branch NOMBRE Rama que debe actualizarse (por defecto: main).
  --remote NOMBRE Remoto Git que debe actualizarse (por defecto: origin).
  -h, --help      Muestra esta ayuda.
EOF
}

while (($#)); do
    case "$1" in
        --no-update) UPDATE_CODE=false ;;
        --skip-kiosk) CONFIGURE_KIOSK=false ;;
        --branch)
            [[ $# -ge 2 ]] || { echo "Falta el nombre de la rama." >&2; exit 2; }
            TARGET_BRANCH="$2"
            shift
            ;;
        --remote)
            [[ $# -ge 2 ]] || { echo "Falta el nombre del remoto." >&2; exit 2; }
            TARGET_REMOTE="$2"
            shift
            ;;
        -h|--help) usage; exit 0 ;;
        *) echo "Opción desconocida: $1" >&2; usage; exit 2 ;;
    esac
    shift
done

log() {
    printf '[%s] %s\n' "$(date '+%Y-%m-%dT%H:%M:%S%z')" "$*" | tee -a "$LOG_FILE"
}

warn() {
    log "AVISO: $*"
}

die() {
    log "ERROR: $*"
    exit 1
}

on_error() {
    local exit_code=$?
    log "El proceso se interrumpió en la línea ${BASH_LINENO[0]} (código ${exit_code})."
    docker compose ps 2>/dev/null | tee -a "$LOG_FILE" || true
    docker compose logs --no-color --tail=160 app 2>/dev/null | tee -a "$LOG_FILE" || true
    log "Diagnóstico guardado en $LOG_FILE"
    exit "$exit_code"
}
trap on_error ERR

as_root() {
    if ((EUID == 0)); then
        "$@"
    elif command -v sudo >/dev/null 2>&1; then
        sudo "$@"
    else
        die "Se necesita root o sudo para corregir permisos y configurar el kiosco."
    fi
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || die "No se encuentra el comando requerido: $1"
}

check_prerequisites() {
    require_command docker
    require_command openssl
    require_command curl
    docker compose version >/dev/null
    if [[ "$UPDATE_CODE" == true ]]; then
        require_command git
    fi
}

update_code() {
    [[ "$UPDATE_CODE" == true ]] || { log "Actualización Git omitida."; return; }

    local current_branch
    current_branch="$(git branch --show-current)"
    [[ -n "$current_branch" ]] || die "El repositorio está en modo detached HEAD."
    [[ "$current_branch" == "$TARGET_BRANCH" ]] || die \
        "La rama activa es '$current_branch'. Cambia a '$TARGET_BRANCH' o usa --branch $current_branch."
    [[ -z "$(git status --porcelain)" ]] || die \
        "Hay cambios locales. Guárdalos o confírmalos antes de ejecutar la recuperación."

    log "Actualizando $TARGET_REMOTE/$TARGET_BRANCH por avance rápido."
    if git fetch "$TARGET_REMOTE" --prune; then
        git pull --ff-only "$TARGET_REMOTE" "$TARGET_BRANCH"
    else
        warn "No se pudo contactar con Git. Se continúa con el código local."
    fi
}

prepare_environment() {
    if [[ ! -f .env ]]; then
        log "Creando .env desde la plantilla."
        cp .env.example .env
    fi

    if ! grep -Eq '^APP_KEY=base64:.+' .env; then
        local app_key
        app_key="base64:$(openssl rand -base64 32 | tr -d '\n')"
        sed -i.bak "s|^APP_KEY=.*|APP_KEY=${app_key}|" .env
        rm -f .env.bak
        log "Se ha generado una APP_KEY segura."
    fi

    if [[ "$(uname -s)" == "Linux" ]]; then
        as_root mkdir -p \
            "$DATA_DIR/database" \
            "$DATA_DIR/media" \
            "$DATA_DIR/thumbnails" \
            "$DATA_DIR/backups" \
            "$DATA_DIR/logs" \
            "$DATA_DIR/cache"
        as_root chown -R 33:33 "$DATA_DIR"
        as_root chmod -R u+rwX,g+rwX "$DATA_DIR"
        as_root chgrp 33 .env
        chmod 0640 .env
    else
        mkdir -p \
            "$DATA_DIR/database" \
            "$DATA_DIR/media" \
            "$DATA_DIR/thumbnails" \
            "$DATA_DIR/backups" \
            "$DATA_DIR/logs" \
            "$DATA_DIR/cache"
        chmod -R u+rwX "$DATA_DIR"
        chmod 0600 .env
    fi

    local http_port
    http_port="$(sed -n 's/^SIMPLEVIEW_HTTP_PORT=//p' .env | tail -n 1 | tr -d '[:space:]\"' || true)"
    http_port="${http_port:-80}"
    [[ "$http_port" =~ ^[0-9]+$ ]] || die "SIMPLEVIEW_HTTP_PORT debe ser un número."
    HTTP_BASE_URL="http://127.0.0.1:${http_port}"
    KIOSK_URL="${KIOSK_URL:-${HTTP_BASE_URL}/display}"
}

backup_database() {
    local database="$DATA_DIR/database/database.sqlite"
    [[ -s "$database" ]] || { log "No existe todavía una base SQLite que respaldar."; return; }

    local backup
    backup="$DATA_DIR/backups/pre-recovery-$(date +%Y%m%d-%H%M%S).sqlite"
    if cp -p "$database" "$backup" 2>/dev/null; then
        :
    else
        as_root cp -p "$database" "$backup"
    fi
    if [[ "$(uname -s)" == "Linux" ]]; then
        as_root chown 33:33 "$backup"
    fi
    log "Copia previa creada: $backup"
}

start_stack() {
    local rebuild_mode="${1:-normal}"

    log "Deteniendo contenedores sin eliminar volúmenes ni datos."
    docker compose down --remove-orphans
    prepare_environment

    if [[ "$rebuild_mode" == "clean" ]]; then
        log "Segundo intento: reconstrucción completa sin caché."
        docker compose build --pull --no-cache app
    else
        log "Construyendo la imagen actualizada."
        docker compose build --pull app
    fi

    docker compose up -d --remove-orphans
}

wait_for_app() {
    local attempt container_id status
    for attempt in $(seq 1 24); do
        container_id="$(docker compose ps -q app 2>/dev/null || true)"
        if [[ -n "$container_id" ]]; then
            status="$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$container_id" 2>/dev/null || true)"
            case "$status" in
                healthy) log "El contenedor app está healthy."; return 0 ;;
                unhealthy|exited|dead)
                    warn "app está $status en el intento $attempt."
                    return 1
                    ;;
            esac
        fi
        sleep 5
    done
    warn "app no alcanzó el estado healthy a tiempo."
    return 1
}

verify_application() {
    log "Comprobando base de datos, migraciones, servicios y funciones críticas."
    docker compose config --quiet
    docker compose exec -T app php artisan simpleview:health-check --quiet-json
    docker compose exec -T app php artisan migrate --force

    local migration_status route_list running_services display_html
    migration_status="$(docker compose exec -T app php artisan migrate:status --no-ansi)"
    grep -q '2026_07_22_000001_add_async_media_processing' <<<"$migration_status"
    grep -q '2026_07_23_000001_add_external_content_to_media_assets' <<<"$migration_status"
    route_list="$(docker compose exec -T app php artisan route:list --no-ansi)"
    grep -q 'visual/aimharder' <<<"$route_list"
    docker compose exec -T app test -f /var/www/html/app/Services/AimHarderEmbedService.php
    docker compose exec -T app test -f /var/www/html/public/images/aimharder-embed.svg

    local service
    running_services="$(docker compose ps --status running --services)"
    for service in app web worker scheduler; do
        grep -qx "$service" <<<"$running_services" \
            || die "El servicio '$service' no está ejecutándose."
    done

    curl --fail --silent --show-error "${HTTP_BASE_URL}/up" >/dev/null
    display_html="$(curl --fail --silent --show-error "${HTTP_BASE_URL}/display")"
    grep -q 'waitForEmbed' <<<"$display_html"
    curl --fail --silent --show-error \
        "${HTTP_BASE_URL}/images/aimharder-embed.svg" >/dev/null
}

configure_kiosk() {
    [[ "$CONFIGURE_KIOSK" == true ]] || { log "Configuración de kiosco omitida."; return; }
    [[ "$(uname -s)" == "Linux" ]] || { warn "El kiosco automático solo se configura en Linux."; return; }
    command -v systemctl >/dev/null 2>&1 \
        || { warn "systemd no está disponible; no se puede configurar el arranque gráfico."; return; }

    local chromium_command=""
    chromium_command="$(command -v chromium-browser 2>/dev/null || command -v chromium 2>/dev/null || true)"
    if [[ -z "$chromium_command" ]] && command -v apt-get >/dev/null 2>&1; then
        log "Chromium no está instalado; instalándolo."
        as_root apt-get update
        as_root apt-get install -y chromium-browser \
            || as_root apt-get install -y chromium
        chromium_command="$(command -v chromium-browser 2>/dev/null || command -v chromium 2>/dev/null || true)"
    fi
    [[ -n "$chromium_command" ]] || die "No se pudo instalar o localizar Chromium."

    if ! id display >/dev/null 2>&1; then
        as_root useradd --create-home --shell /bin/bash display
    fi

    local kiosk_tmp desktop_tmp gdm_tmp
    kiosk_tmp="$(mktemp)"
    desktop_tmp="$(mktemp)"
    cat >"$kiosk_tmp" <<EOF
#!/usr/bin/env bash
set -u
KIOSK_URL="${KIOSK_URL}"
while ! curl --fail --silent --max-time 3 "${HTTP_BASE_URL}/up" >/dev/null; do
    sleep 2
done
while true; do
    "${chromium_command}" \\
        --kiosk \\
        --noerrdialogs \\
        --disable-infobars \\
        --disable-session-crashed-bubble \\
        --autoplay-policy=no-user-gesture-required \\
        "\$KIOSK_URL"
    sleep 2
done
EOF
    cat >"$desktop_tmp" <<'EOF'
[Desktop Entry]
Type=Application
Name=Simple View
Exec=/usr/local/bin/simple-view-kiosk
X-GNOME-Autostart-enabled=true
EOF

    as_root install -m 0755 "$kiosk_tmp" /usr/local/bin/simple-view-kiosk
    as_root install -d -o display -g display /home/display/.config/autostart
    as_root install -o display -g display -m 0644 \
        "$desktop_tmp" /home/display/.config/autostart/simple-view.desktop
    rm -f "$kiosk_tmp" "$desktop_tmp"

    if [[ -f /etc/gdm3/custom.conf ]]; then
        gdm_tmp="$(mktemp)"
        awk '
            /^AutomaticLoginEnable=/ { next }
            /^AutomaticLogin=/ { next }
            /^\[daemon\]$/ {
                print
                print "AutomaticLoginEnable=true"
                print "AutomaticLogin=display"
                configured=1
                next
            }
            { print }
            END {
                if (!configured) {
                    print "[daemon]"
                    print "AutomaticLoginEnable=true"
                    print "AutomaticLogin=display"
                }
            }
        ' /etc/gdm3/custom.conf >"$gdm_tmp"
        as_root install -m 0644 "$gdm_tmp" /etc/gdm3/custom.conf
        rm -f "$gdm_tmp"
    else
        warn "No existe /etc/gdm3/custom.conf; revisa el inicio automático del usuario display."
    fi

    as_root systemctl enable docker
    as_root systemctl set-default graphical.target
    if systemctl list-unit-files --type=service | grep -q '^gdm3.service'; then
        as_root systemctl enable gdm3
    fi

    as_root test -x /usr/local/bin/simple-view-kiosk
    as_root test -f /home/display/.config/autostart/simple-view.desktop
    as_root grep -q 'Exec=/usr/local/bin/simple-view-kiosk' \
        /home/display/.config/autostart/simple-view.desktop
    if [[ -f /etc/gdm3/custom.conf ]]; then
        as_root grep -q '^AutomaticLoginEnable=true$' /etc/gdm3/custom.conf
        as_root grep -q '^AutomaticLogin=display$' /etc/gdm3/custom.conf
    fi
    log "Kiosco verificado. Se abrirá $KIOSK_URL al iniciar la sesión gráfica."
    KIOSK_READY=true
}

main() {
    log "Iniciando recuperación segura de Simple View."
    log "Los diagnósticos se guardarán en $LOG_FILE"
    check_prerequisites
    update_code
    prepare_environment

    docker compose down --remove-orphans
    backup_database

    if ! start_stack normal || ! wait_for_app; then
        warn "El primer arranque falló. Se aplicará una reconstrucción autocorrectiva."
        docker compose logs --no-color --tail=160 app | tee -a "$LOG_FILE" || true
        start_stack clean
        wait_for_app || die "app continúa sin estar healthy después del segundo intento."
    fi

    verify_application
    configure_kiosk
    docker compose ps
    if [[ "$KIOSK_READY" == true ]]; then
        log "Recuperación completada. Simple View está disponible y el kiosco queda preparado para el próximo reinicio."
    else
        log "Recuperación completada. Simple View está disponible."
    fi
}

main "$@"
