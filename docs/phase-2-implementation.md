# Informe de implementación de la Fase 2

## Compatibilidad

La implementación existente ya ofrecía modelos separados para diseños, zonas, listas y medios, además de publicación inmutable y vista previa. La Fase 2 se ha añadido sin reescribirlos: la migración es aditiva, el editor clásico sigue disponible como respaldo y el editor visual se controla con `SIMPLEVIEW_VISUAL_EDITOR_ENABLED`.

Antes de migrar se creó una copia de configuración y SQLite y se verificó su checksum SHA-256. La publicación y `/display` continúan usando `PublicationService` y el mismo snapshot estable.

## Editor visual

- Selector de seis plantillas y lienzo CSS Grid 16:9.
- Biblioteca integrada con búsqueda, filtro y botones accesibles.
- Arrastre de medios y de archivos del equipo directamente a una zona.
- Inspector por zona, reordenación, valores predeterminados y ajustes individuales de imágenes.
- Autoguardado con debounce, indicador de estado, reintento y recuperación mediante almacenamiento local.
- Cambio de plantilla conservador; si se reducen zonas, exige confirmación y reasigna los contenidos a una zona conservada.
- Vista previa existente y publicación mediante el servicio estable, bloqueada ante guardados fallidos o almacenamiento crítico.

## Almacenamiento

La política reside en servicios de dominio, no en controladores. La reserva técnica nunca baja de 15 GB. Las subidas calculan original, temporal y margen con un multiplicador mínimo de 2,0 (2,1 por defecto), y se vuelven a comprobar al procesar el archivo.

Comandos disponibles:

```bash
docker compose exec app php artisan simpleview:storage-report --refresh
docker compose exec app php artisan simpleview:storage-reconcile
docker compose exec app php artisan simpleview:find-orphans --json
docker compose exec app php artisan simpleview:cleanup-storage --dry-run
```

`cleanup-storage` solo ejecuta cambios con `--force`; nunca borra multimedia válida. `scripts/storage-report.sh` se ejecuta en el host y escribe JSON atómico sin montar el socket de Docker. `scripts/docker-maintenance.sh` requiere escribir `LIMPIAR` y no elimina volúmenes.

Para producción se recomienda programar `scripts/storage-report.sh` cada cinco minutos mediante cron o un timer de systemd. La aplicación actualiza métricas rápidas cada cinco minutos, reconcilia diariamente y aplica retención segura a temporales, logs, backups y miniaturas huérfanas.

## Integración AIMHARDER

Se añadió el tipo de contenido `web_embed` para páginas públicas de AIMHARDER. El sistema guarda URL, proveedor, opciones de refresco, interacción y respaldo como datos estructurados en `media_assets`; no guarda HTML arbitrario.

Reglas principales:

- solo se aceptan URLs `https`;
- se rechazan credenciales, esquemas peligrosos y hosts internos;
- la whitelist técnica por defecto es `aimharder.com,*.aimharder.com`;
- un bloque AIMHARDER ocupa una zona completa y no se mezcla con imágenes o vídeos;
- el editor visual muestra una tarjeta ligera y el iframe real solo se carga en vista previa o `/display`;
- la CSP permite `frame-src 'self' https://aimharder.com https://*.aimharder.com`;
- la eliminación usa las mismas reglas seguras que el resto de contenidos y se bloquea si el bloque está publicado.

La comprobación HTTP de `https://gamancrossfit.aimharder.com/navwod` respondió 200 sin cabeceras evidentes `X-Frame-Options` ni CSP bloqueante. El entorno de desarrollo no incluye Chromium, así que la verificación visual final debe hacerse en el navegador del equipo que use el panel.
