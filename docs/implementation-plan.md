# Plan de implementación

`docs/codex_context.md` es la fuente de verdad funcional. El trabajo se divide en ocho fases verificables:

1. **Base (implementada):** Laravel 12, Filament 3, SQLite, login, configuración, Docker Compose, Nginx, administrador inicial y healthchecks.
2. **Biblioteca multimedia:** subida por streaming, SHA-256, ffprobe, miniaturas, búsqueda y eliminación segura.
3. **Editor:** seis plantillas CSS Grid, zonas, listas mixtas, ordenación y borrador.
4. **Reproductor y vista previa:** reproducción independiente, transiciones, caché local y recuperación.
5. **Publicación y estado:** snapshots, versión, sondeo, heartbeat y dashboard operativo.
6. **Horarios y respaldo:** intervalos semanales, overrides e imagen corporativa.
7. **Copias y despliegue:** backup/restore, rollback, kiosco y automatización del host.
8. **Endurecimiento:** pruebas E2E, seguridad, rendimiento y recuperación ante disco lleno.

## Estructura propuesta

```text
app/                 dominio, comandos y panel Filament
database/            migraciones, factories y seeders
docker/app/          imagen PHP-FPM y arranque
docker/nginx/        servidor web LAN
public/              punto de entrada y recursos públicos
resources/           Blade, CSS y JavaScript del reproductor
routes/              web y API interna
scripts/             provisión, despliegue, backup y salud
data/                persistencia local (ignorada por Git)
tests/               pruebas unitarias e integración
docs/                especificación y operación
```

Antes de comenzar una fase se ejecutarán las pruebas existentes y, al terminar, sus criterios relacionados en la sección 33 de la especificación.
