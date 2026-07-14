# Simple View

Aplicación local de cartelería digital para una pantalla. Esta entrega contiene la **Fase 1 funcional** descrita en [el plan](docs/implementation-plan.md): Laravel, Filament, SQLite, Nginx y Docker Compose.

## Arranque rápido

Requisitos: Docker Engine, Docker Compose Plugin y OpenSSL.

```bash
./scripts/install.sh
```

Después abre `http://localhost/admin` e inicia sesión con:

- Usuario: `admin`
- Contraseña inicial: `admin123`

En la esquina superior derecha abre el menú del usuario, entra en **Perfil** y cambia la contraseña. El instalador crea `.env`, genera automáticamente una clave segura, prepara los directorios persistentes, construye las imágenes y arranca la aplicación.

Si necesitas ejecutar los pasos manualmente:

```bash
cp .env.example .env
mkdir -p data/{database,media,thumbnails,backups,logs,cache}
docker compose build
docker compose up -d
docker compose ps
```

El panel queda en `http://localhost/admin` (o `http://simpleview.local/admin` cuando mDNS esté configurado). El contenedor ejecuta las migraciones y crea una sola vez el administrador indicado en `.env`.

## Operación

```bash
./scripts/health-check.sh
docker compose logs -f
docker compose exec app php artisan migrate --force
docker compose exec app php artisan simpleview:create-admin
docker compose exec app php artisan simpleview:reset-admin-password
```

Los datos están en `data/`, fuera de las capas de los contenedores. La base SQLite se guarda en `data/database/database.sqlite`; los directorios de medios, miniaturas, copias, logs y caché ya están reservados para las fases siguientes.

## Ubuntu 24.04 LTS

En una instalación limpia, clona el repositorio y ejecuta:

```bash
sudo bash scripts/provision-host.sh
```

El script instala Docker, Git, Chromium y mDNS, crea `/opt/simple-view`, genera una clave de aplicación y levanta los servicios. Por seguridad se detiene para que se cambie la contraseña predeterminada. El login automático y el kiosco se completarán con el reproductor en la Fase 7; esta desviación está expresamente acotada en el plan.

Si `simpleview.local` no resuelve, usa la IP privada mostrada por el instalador y configura una reserva DHCP. No abras el puerto en el router.

## Uso diario

1. En **Contenidos**, pulsa **Subir archivos** y selecciona o arrastra imágenes JPEG/PNG/WebP y vídeos MP4/H.264. Los duplicados se reutilizan automáticamente.
2. En **Editar pantalla**, crea un diseño y elige una de las seis plantillas.
3. Abre el diseño, añade contenidos a cada zona, ordena la lista y configura duración, ajuste y transición.
4. Pulsa **Vista previa** para comprobar el borrador sin modificar la televisión.
5. Pulsa **Publicar**. `/display` recibe la nueva versión en un máximo aproximado de tres segundos.
6. Configura los intervalos en **Horarios**. En **Configuración** puedes pausar, forzar temporalmente la reproducción y seleccionar la imagen de respaldo.
7. Consulta el estado en **Inicio** y crea o descarga copias desde **Copias de seguridad**.

El reproductor utiliza `http://localhost/display`. Continúa con la última publicación almacenada en el navegador si el servidor deja de responder temporalmente.

El tamaño máximo se configura con `SIMPLEVIEW_MAX_UPLOAD_MB`. El valor `0` elimina el límite funcional, pero siempre se respeta `SIMPLEVIEW_MAX_UPLOAD_HARD_MB` (4 GB por defecto), además del espacio disponible en disco.

## Copias, restauración y despliegue

```bash
./scripts/backup.sh
./scripts/backup.sh --full
./scripts/restore.sh data/backups/NOMBRE.tar.gz
./scripts/deploy.sh
./scripts/rollback.sh COMMIT_O_TAG
```

Las copias de configuración incluyen SQLite y `.env`; las completas añaden originales y miniaturas. Cada archivo incluye un checksum SHA-256.

## Funcionalidad implementada

Biblioteca multimedia, inspección con FFmpeg/ffprobe, miniaturas, deduplicación SHA-256, seis plantillas, zonas independientes, listas mixtas reordenables, borradores, vista previa, snapshots publicados, sondeo, caché sin conexión, heartbeat, registro de errores, horario semanal, respaldo, dashboard, copias locales y scheduler diario.

La configuración del login gráfico automático y Chromium kiosco corresponde al equipo Ubuntu físico y se aplica durante la provisión final del OptiPlex. La especificación completa permanece en [docs/codex_context.md](docs/codex_context.md).
