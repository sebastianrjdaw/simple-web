# Simple View

Aplicación local de cartelería digital para una pantalla. Incluye la Fase 1 y la **Fase 2 de editor visual y control de almacenamiento**, descrita en [su informe](docs/phase-2-implementation.md).

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
2. En **Diseños**, crea un diseño y elige una de las seis plantillas.
3. El sistema abre el **Editor visual**. Arrastra contenidos o archivos directamente a cada zona, ordena la lista y configura duración, ajuste y transición.
4. Pulsa **Vista previa** para comprobar el borrador sin modificar la televisión.
5. Pulsa **Publicar**. `/display` recibe la nueva versión en un máximo aproximado de tres segundos.
6. Configura los intervalos en **Horarios**. En **Configuración** puedes pausar, forzar temporalmente la reproducción y seleccionar la imagen de respaldo.
7. Consulta el estado en **Inicio**, fuerza la recarga del reproductor cuando lo necesites y gestiona copias desde **Copias de seguridad**.

El reproductor utiliza `http://localhost/display`. Continúa con la última publicación almacenada en el navegador si el servidor deja de responder temporalmente.

El tamaño máximo se configura con `SIMPLEVIEW_MAX_UPLOAD_MB`. El valor `0` elimina el límite funcional, pero siempre se respeta `SIMPLEVIEW_MAX_UPLOAD_HARD_MB` (4 GB por defecto), además del espacio disponible en disco.

## Integración con AIMHARDER

Simple View puede mostrar el WOD público de AIMHARDER en una zona del diseño sin pegar HTML. En el editor visual pulsa **WOD de AIMHARDER**, revisa la URL pública, por ejemplo `https://gamancrossfit.aimharder.com/navwod`, y pulsa **Añadir a esta zona**.

El bloque AIMHARDER ocupa la zona completa: no se mezcla con imágenes ni vídeos en esa misma zona. En otras zonas puedes seguir usando listas normales de fotos y vídeos.

Opciones disponibles:

- actualización del iframe cada 5, 15, 30 o 60 minutos, o sin recarga;
- interacción desactivada por defecto para evitar clics accidentales en la pantalla;
- imagen local de respaldo;
- prueba de visualización desde el panel, que abre una ventana con el mismo iframe/sandbox que usará el reproductor.

La página necesita Internet y depende de que AIMHARDER permita mostrarse dentro de un iframe. Si el proveedor bloquea la incrustación mediante `X-Frame-Options` o CSP, Simple View no intenta saltarse esa protección: solicita a AIMHARDER una URL pública preparada para incrustar.

La política CSP permite marcos solo desde Simple View y AIMHARDER:

```text
frame-src 'self' https://aimharder.com https://*.aimharder.com;
```

Los dominios permitidos se configuran técnicamente con `SIMPLEVIEW_WEB_EMBED_ALLOWED_HOSTS`; no hay editor libre de dominios en el panel para evitar usar iframes hacia hosts internos o no autorizados.

La Fase 2 protege una reserva mínima de 15 GB. En **Inicio → Almacenamiento** se muestran uso real, desglose, recursos grandes, contenidos sin utilizar y diseños no activos. Las eliminaciones pasan por reglas seguras: no se borra contenido usado por la publicación activa ni por la imagen de respaldo. Para generar el informe complementario del host sin exponer Docker a la aplicación:

```bash
./scripts/storage-report.sh
docker compose exec app php artisan simpleview:storage-report
docker compose exec app php artisan simpleview:cleanup-storage --dry-run
```

## Copias, restauración y despliegue

```bash
./scripts/backup.sh
./scripts/backup.sh --full
./scripts/restore.sh data/backups/NOMBRE.tar.gz
./scripts/deploy.sh
./scripts/rollback.sh COMMIT_O_TAG
```

Las copias de configuración incluyen SQLite y `.env`; las completas añaden originales y miniaturas. Cada archivo incluye un checksum SHA-256.

Desde **Copias de seguridad → Configurar** puedes elegir copias diarias o cada dos días, hora, tipo y retención. La aplicación no acepta intervalos superiores a 48 horas y el scheduler comprueba copias pendientes cada quince minutos, de forma que recupera una copia perdida tras un apagado.

## Funcionalidad implementada

Biblioteca multimedia, inspección con FFmpeg/ffprobe, miniaturas, deduplicación SHA-256, seis plantillas, editor visual principal, zonas independientes, listas mixtas reordenables, borradores, vista previa, snapshots publicados, sondeo, recarga forzada del reproductor, caché sin conexión, heartbeat, registro de errores, horario semanal, respaldo, dashboard, borrado seguro, almacenamiento accionable, copias locales y scheduler recuperable.

La configuración del login gráfico automático y Chromium kiosco corresponde al equipo Ubuntu físico y se aplica durante la provisión final del OptiPlex. La especificación completa permanece en [docs/codex_context.md](docs/codex_context.md).
