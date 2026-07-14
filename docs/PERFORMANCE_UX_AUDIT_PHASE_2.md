# Auditoría de rendimiento y UX — ampliación de Fase 2

Fecha: 2026-07-14

## Diferencias detectadas

- El proyecto no tenía un controlador exclusivo de editor clásico. El editor clásico era el formulario de Filament de `LayoutResource`.
- El editor visual ya existía y era funcional, pero el listado todavía mostraba acciones hacia el formulario clásico.
- La página de almacenamiento era informativa y eliminaba contenidos sin pasar por un servicio de dominio común.
- Las copias automáticas dependían de una expresión cron por `.env`; no existía configuración visual con límite de 48 horas.
- Los listados tenían buscador básico en algunos campos, pero faltaban filtros combinables y ordenaciones más útiles.

## Cambios aplicados

- Se añadió `MediaDeletionService` para clasificar y ejecutar eliminaciones de contenidos desde cualquier pantalla.
- Se añadió `LayoutDeletionService` para borrar diseños sin tocar multimedia y bloquear el diseño activo o el último diseño.
- Se añadió `admin_activity_events` para registrar borrados, bloqueos, errores, usuario, tamaño y detalles.
- El editor visual pasó a ser la única edición visible; `/admin/layouts/{id}/edit` redirige al editor visual.
- El selector del editor visual permite ver usos y eliminar contenidos con las mismas reglas seguras.
- La página de almacenamiento incluye filtros, archivos grandes seleccionables, contenidos no utilizados y diseños no activos.
- Las copias de seguridad se configuran desde el panel con frecuencia diaria o cada dos días, hora, tipo y retención.
- El scheduler de backups se ejecuta cada 15 minutos y el servicio decide si toca copia, cubriendo apagados.
- El dashboard muestra alerta de copias cuando no existe copia válida reciente.

## Rendimiento

- Se añadió carga máxima de 500 recursos en el estado inicial del editor visual. Esto evita traer toda la biblioteca en instalaciones grandes.
- La búsqueda del editor visual queda en cliente sobre esos recursos iniciales; para una biblioteca mayor se pospone endpoint paginado.
- La página de almacenamiento filtra en servidor por tipo, nombre, uso y orden.
- Las métricas de almacenamiento siguen usando snapshots y cache temporal para evitar recorrer directorios en cada carga.
- El borrado masivo procesa resultados por elemento para no abortar toda la operación ante un bloqueo individual.

## Índices añadidos

- `media_assets(media_type, status)`
- `media_assets(file_size)`
- `media_assets(last_used_at)`
- `media_assets(created_at)`
- `layouts(state, version)`
- `layouts(template_key)`
- `layouts(updated_at)`
- `backups(status, completed_at)`
- `backups(type, started_at)`
- `admin_activity_events(action, result)`
- `admin_activity_events(subject_type, subject_id)`

## UX

- Las acciones destructivas explican si el contenido está bloqueado por publicación activa, configuración o diseños no activos.
- Los diseños aclaran que borrar una composición no elimina los archivos multimedia.
- El almacenamiento pasa de pantalla informativa a espacio de limpieza accionable.
- La terminología visible se ha alineado alrededor de Contenido, Diseño, Plantilla, Zona, Vista previa, Publicar, Eliminar y Copia de seguridad.
- El dashboard prioriza reproductor, publicación activa, contenidos, almacenamiento y copias.

## Validación

- Suite automática ejecutada: 20 tests, 64 aserciones.
- Casos añadidos: borrado seguro de contenido no usado, bloqueo por publicación activa, bloqueo por imagen de respaldo, retirada desde diseños inactivos, borrado de diseño inactivo, bloqueo de diseño activo, redirección de ruta clásica y límite de backups a dos días.
- Se creó una copia local antes de migrar: `simple-view-configuration-20260714-165141-8v5snu.tar.gz`.
- Migración aplicada: `2026_07_14_000005_add_phase_two_ampliation`.

## Pendientes razonables

- Convertir la biblioteca del editor visual a paginación real con endpoint dedicado cuando la instalación supere 500 contenidos frecuentes.
- Añadir una pantalla de detalle de actividad para `admin_activity_events` si el cliente necesita auditoría visible.
- Añadir destinos USB/NAS configurables cuando exista una ruta concreta de instalación.
- Mover backups pesados a cola de base de datos si las exportaciones completas empiezan a bloquear peticiones en hardware real.
- Añadir restauración asistida desde panel solo cuando el flujo de rollback físico esté completamente cerrado.
