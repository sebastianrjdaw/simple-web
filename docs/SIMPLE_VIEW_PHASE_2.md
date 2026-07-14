# Simple View — Fase 2: editor visual y control de almacenamiento

## 1. Propósito

Este documento define la **Fase 2 de evolución de Simple View**.

La aplicación ya dispone de una versión estable y funcional. Esta fase no consiste en reconstruir el proyecto ni sustituir su arquitectura, sino en mejorar dos áreas concretas:

1. Crear una experiencia visual e intuitiva para seleccionar plantillas y asignar imágenes o vídeos a sus zonas.
2. Incorporar un sistema fiable de medición, prevención y mantenimiento del espacio de almacenamiento del mini PC.

Codex debe usar este documento junto con `SIMPLE_VIEW_CODEX_CONTEXT.md` y con el código existente del repositorio.

La prioridad es mantener la estabilidad actual. Cualquier decisión no especificada debe resolverse en este orden:

1. No romper la reproducción ni la publicación existentes.
2. No perder datos, archivos, borradores ni configuraciones.
3. Mantener la interfaz comprensible para una persona no técnica.
4. Evitar dependencias innecesarias.
5. Mantener el funcionamiento sin conexión a Internet.
6. Priorizar soluciones fáciles de mantener mediante Docker Compose y Git.

---

## 2. Instrucción principal para Codex

Antes de modificar código:

1. Lee completamente `SIMPLE_VIEW_CODEX_CONTEXT.md`.
2. Inspecciona la arquitectura, modelos, migraciones, servicios, rutas, componentes y pruebas existentes.
3. Identifica cómo se implementan actualmente:
   - borradores;
   - publicaciones;
   - plantillas;
   - zonas;
   - listas de reproducción;
   - biblioteca multimedia;
   - subidas;
   - miniaturas;
   - almacenamiento persistente;
   - backups;
   - logs;
   - vista previa;
   - reproductor.
4. Genera un informe breve de compatibilidad y un plan de cambios incrementales.
5. No reemplaces componentes estables sin justificarlo.
6. Reutiliza servicios y reglas de dominio existentes.
7. Añade migraciones compatibles hacia delante. No borres columnas ni datos existentes.
8. Realiza una copia de seguridad antes de ejecutar migraciones en producción.
9. Implementa la fase en cambios pequeños, verificables y reversibles.
10. Ejecuta las pruebas después de cada bloque relevante.

No realices una reescritura general del proyecto.

---

# PARTE A — EDITOR VISUAL DE PANTALLA

## 3. Objetivo de experiencia de usuario

El editor debe permitir que el administrador comprenda la composición de la televisión sin conocer conceptos técnicos.

El flujo principal debe quedar reducido a:

```text
Elegir plantilla → Arrastrar contenidos → Ordenar → Vista previa → Publicar
```

La plantilla debe representarse como un **lienzo 16:9**, visualmente similar a la televisión real.

Cada zona debe ser un destino grande y evidente para arrastrar imágenes o vídeos.

Ejemplo para una cuadrícula 2 × 2:

```text
┌────────────────────────┬────────────────────────┐
│                        │                        │
│        Zona 1          │        Zona 2          │
│  Arrastra contenido    │  Arrastra contenido    │
│                        │                        │
├────────────────────────┼────────────────────────┤
│                        │                        │
│        Zona 3          │        Zona 4          │
│  Arrastra contenido    │  Arrastra contenido    │
│                        │                        │
└────────────────────────┴────────────────────────┘
```

El editor no debe parecer un formulario administrativo complejo. El lienzo debe ser su elemento principal.

---

## 4. Estructura de la página

Crear o mejorar una página personalizada del panel administrativo con estas áreas:

### 4.1 Barra superior

Debe mostrar:

- nombre de la pantalla;
- estado del reproductor;
- estado del borrador;
- indicador de autoguardado;
- botón `Vista previa`;
- botón principal `Publicar`.

Estados de guardado:

```text
Cambios pendientes
Guardando…
Guardado
No se pudo guardar
```

El botón `Publicar` debe ser la acción visual principal, pero nunca debe ejecutarse automáticamente.

### 4.2 Selector visual de plantillas

Las plantillas se mostrarán como tarjetas con miniaturas esquemáticas, no únicamente como un desplegable.

Plantillas mínimas:

1. Pantalla completa.
2. Dos columnas.
3. Dos filas.
4. Cuadrícula 2 × 2.
5. Dos zonas horizontales y lateral derecho.
6. Lateral izquierdo y dos zonas horizontales.

Cada tarjeta debe mostrar:

- esquema de la distribución;
- nombre;
- número de zonas;
- estado seleccionado.

### 4.3 Lienzo 16:9

Debe:

- conservar proporción 16:9;
- usar CSS Grid;
- reflejar las proporciones de la plantilla;
- adaptarse al espacio disponible;
- usar fondo oscuro;
- delimitar claramente cada zona;
- permitir seleccionar una zona;
- aceptar contenidos mediante arrastrar y soltar;
- mostrar feedback visual mientras se arrastra.

### 4.4 Biblioteca multimedia

Debe permanecer disponible sin abandonar el editor.

Puede situarse:

- como bandeja inferior expandible; o
- como panel lateral secundario.

Debe incluir:

- buscador;
- filtros `Todos`, `Imágenes` y `Vídeos`;
- miniaturas;
- nombre;
- tamaño;
- duración de vídeos;
- indicador de uso;
- selección múltiple;
- botón `Subir archivos`;
- arrastrar desde Finder;
- paginación o carga progresiva.

### 4.5 Inspector de zona

Al seleccionar una zona debe abrirse un panel contextual con:

- nombre de la zona;
- número de contenidos;
- lista ordenada;
- miniaturas;
- nombre y tipo;
- controles para reordenar;
- configuración de imágenes;
- información de vídeos;
- acción para retirar un elemento de la zona;
- botón `Añadir contenido`;
- botón `Subir a esta zona`.

Retirar un elemento de una zona no debe eliminarlo de la biblioteca.

---

## 5. Estados visuales de las zonas

### 5.1 Zona vacía

Debe mostrar:

```text
Zona 1

Arrastra imágenes o vídeos aquí

[Seleccionar contenido] [Subir archivo]
```

Toda la zona debe funcionar como destino de arrastre.

Al arrastrar sobre ella:

- destacar borde y fondo;
- mostrar `Soltar para añadir a Zona X`;
- retirar el estado al salir.

### 5.2 Zona con un contenido

Debe mostrar:

- miniatura grande;
- nombre del contenido;
- icono de imagen o vídeo;
- etiqueta `1 contenido`;
- acción para editar la zona.

### 5.3 Zona con varios contenidos

Debe mostrar:

- miniatura del primer elemento;
- pequeñas miniaturas superpuestas o una tira de siguientes elementos;
- contador, por ejemplo `5 contenidos`;
- iconos de tipos presentes;
- etiqueta `En bucle`.

No reproducir automáticamente todos los vídeos dentro del editor. Usar sus miniaturas o pósteres.

La reproducción real se hará en la vista previa y en `/display`.

---

## 6. Reglas de arrastrar y soltar

### 6.1 Desde la biblioteca hacia una zona

Al soltar uno o varios recursos:

1. Añadirlos al final de la lista de la zona.
2. No reemplazar el contenido existente.
3. No duplicar físicamente el archivo.
4. Actualizar inmediatamente el contador.
5. Seleccionar la zona.
6. Actualizar el inspector.
7. Programar el autoguardado.
8. Mostrar una confirmación breve.

Ejemplo:

```text
3 contenidos añadidos a Zona 2
```

### 6.2 Desde Finder hacia una zona

El usuario podrá soltar archivos directamente desde macOS sobre una zona.

El flujo será:

1. Validar tipo y tamaño.
2. Comprobar espacio disponible antes de aceptar la subida.
3. Mostrar progreso por archivo.
4. Crear el recurso multimedia.
5. Generar miniatura.
6. Añadirlo automáticamente a esa zona.
7. Mostrar errores de forma individual.

No obligar al usuario a subir el archivo, ir a la biblioteca, buscarlo y añadirlo después.

### 6.3 Reordenación

La lista del inspector debe permitir reordenar elementos por arrastre.

El orden debe persistir sin recargar toda la página.

### 6.4 Accesibilidad alternativa

Toda acción de arrastrar y soltar debe tener una alternativa por botones para usuarios o navegadores donde el arrastre no funcione correctamente.

---

## 7. Configuración de contenidos

### 7.1 Imágenes

Cada imagen debe permitir:

- duración individual;
- modo `Cubrir`;
- modo `Contener`;
- transición `Corte`;
- transición `Fundido`;
- duración de fundido.

Valores predeterminados:

```text
Duración: 10 segundos
Ajuste: Cubrir
Transición: Fundido
Fundido: 500 ms
```

No mostrar términos CSS al cliente.

### 7.2 Vídeos

Mostrar:

- miniatura;
- duración;
- resolución;
- tamaño;
- compatibilidad;
- indicación `Se reproduce completo`;
- indicación `Sin sonido`.

No permitir recortar artificialmente la duración en esta fase.

### 7.3 Configuración predeterminada de zona

Cada zona puede tener:

- duración predeterminada de imágenes;
- ajuste predeterminado;
- transición predeterminada;
- duración predeterminada de transición.

Los valores individuales pueden sobrescribir los valores de la zona.

La interfaz debe distinguir claramente ambos niveles.

---

## 8. Cambio seguro de plantilla

Cambiar de plantilla nunca debe provocar pérdida silenciosa de asociaciones.

### 8.1 Plantilla con igual o mayor número de zonas

- conservar zonas compatibles por clave u orden;
- crear vacías las zonas nuevas;
- mostrar un resumen.

### 8.2 Plantilla con menos zonas

Mostrar un diálogo de reasignación:

- indicar qué zonas desaparecerán;
- listar sus contenidos;
- permitir moverlos a otra zona;
- permitir dejarlos solo en la biblioteca;
- permitir cancelar.

No borrar archivos físicos.

No confirmar el cambio hasta resolver la reasignación.

---

## 9. Autoguardado

Todos los cambios del borrador deben guardarse automáticamente con `debounce`.

Requisitos:

- evitar una petición por cada movimiento;
- esperar a que termine una reordenación antes de guardar;
- conservar estado local si falla la red;
- mostrar error visible;
- ofrecer `Reintentar`;
- no permitir publicar mientras haya un guardado fallido o pendiente;
- no convertir el autoguardado en publicación.

---

## 10. Vista previa

El botón `Vista previa` abrirá una ventana independiente con el borrador actual.

Debe:

- mantener proporción 16:9;
- reproducir vídeos reales;
- ejecutar zonas independientemente;
- utilizar las mismas reglas que `/display`;
- mostrar la etiqueta `Vista previa`;
- no enviar heartbeat como pantalla física;
- no modificar la publicación activa;
- permitir actualizar la simulación tras cambios.

---

## 11. Publicación

Al pulsar `Publicar`:

1. Completar el guardado pendiente.
2. Validar plantilla y zonas.
3. Validar existencia y estado de archivos.
4. Comprobar que no exista una condición crítica de almacenamiento.
5. Mostrar un resumen.
6. Solicitar confirmación.
7. Crear la publicación mediante el mecanismo estable ya existente.
8. Mostrar confirmación.
9. Indicar que la pantalla se actualizará en un máximo de tres segundos.

No mostrar excepciones técnicas al usuario.

---

# PARTE B — GESTIÓN DE ALMACENAMIENTO

## 12. Objetivo

Evitar que Simple View llene el SSD del OptiPlex y provoque:

- fallos de subida;
- corrupción de SQLite;
- problemas de logs;
- fallos al crear backups;
- imposibilidad de desplegar actualizaciones;
- pérdida de rendimiento;
- bloqueo del sistema operativo.

La aplicación debe distinguir entre:

1. Espacio total y libre del sistema de archivos.
2. Espacio administrado directamente por Simple View.
3. Espacio ocupado por Docker y elementos externos a la biblioteca.
4. Espacio recuperable de forma segura.

No confiar únicamente en sumar el campo `file_size` de la base de datos.

---

## 13. Categorías de consumo

Mostrar, como mínimo:

- archivos multimedia originales;
- miniaturas;
- base de datos SQLite;
- copias de seguridad;
- logs de aplicación;
- caché;
- archivos temporales y subidas incompletas;
- código y archivos del proyecto;
- imágenes, capas, volúmenes y caché de Docker;
- otros archivos del sistema.

La suma debe presentarse de forma comprensible.

Ejemplo:

```text
Multimedia               42,8 GB
Copias de seguridad       6,2 GB
Miniaturas               850 MB
Base de datos              18 MB
Logs                       96 MB
Temporales                 12 MB
Docker                    8,4 GB
Sistema y otros          24,1 GB
────────────────────────────────
Usado total              82,5 GB
Libre                    35,7 GB
```

---

## 14. Dos niveles de medición

### 14.1 Medición dentro de la aplicación

Crear un servicio, por ejemplo `StorageMetricsService`, que mida:

- capacidad del sistema de archivos donde reside `/data`;
- espacio libre;
- uso de directorios persistentes;
- suma de recursos registrados;
- archivos huérfanos;
- registros cuyo archivo no existe;
- temporales antiguos;
- backups y retención;
- archivos más grandes.

Usar las funciones adecuadas del sistema operativo o PHP sin cargar archivos en memoria.

La medición recursiva de directorios grandes no debe realizarse en cada petición web.

Implementar:

- caché de métricas;
- actualización programada;
- actualización manual;
- reconciliación profunda menos frecuente.

Frecuencias sugeridas:

```text
Métricas rápidas: cada 5 minutos
Reconciliación profunda: una vez al día
Tras subida, borrado o backup: actualización incremental
```

### 14.2 Medición del host

La aplicación dentro del contenedor no debe recibir acceso al socket de Docker.

Crear un script de host:

```text
scripts/storage-report.sh
```

Debe recopilar de forma segura:

- salida de `df` del sistema de archivos relevante;
- tamaño de `/opt/simple-view`;
- tamaño de directorios persistentes;
- información de `docker system df`;
- espacio recuperable de caché de compilación;
- fecha de la medición.

Debe escribir un JSON atómico en:

```text
/opt/simple-view/data/metrics/storage-host.json
```

La aplicación leerá este archivo en modo solo lectura.

No montar `/var/run/docker.sock` dentro del contenedor web.

El script debe poder ejecutarse mediante:

- `systemd timer`; o
- cron del host.

Frecuencia sugerida: cada 5 minutos.

Si el informe está desactualizado, la interfaz debe indicarlo.

---

## 15. Umbrales y reserva de seguridad

Variables sugeridas:

```env
SIMPLEVIEW_STORAGE_WARNING_PERCENT=80
SIMPLEVIEW_STORAGE_BLOCK_PERCENT=90
SIMPLEVIEW_STORAGE_RESERVE_GB=15
SIMPLEVIEW_STORAGE_HOST_REPORT_MAX_AGE_MINUTES=15
SIMPLEVIEW_STORAGE_SCAN_INTERVAL_MINUTES=5
SIMPLEVIEW_STORAGE_DEEP_SCAN_HOUR=3
SIMPLEVIEW_TEMP_RETENTION_HOURS=24
SIMPLEVIEW_LOG_RETENTION_DAYS=14
SIMPLEVIEW_BACKUP_RETENTION_DAYS=7
```

La decisión de permitir una subida no puede depender únicamente de porcentajes.

### Estado normal

- uso inferior al umbral de aviso;
- espacio libre proyectado superior a la reserva.

### Estado de aviso

Se activa si se cumple cualquiera:

- uso igual o superior al 80 %;
- espacio libre igual o inferior a 20 GB;
- crecimiento anormal;
- informe de host desactualizado.

### Estado crítico

Se activa si se cumple cualquiera:

- uso igual o superior al 90 %;
- espacio libre igual o inferior a 15 GB;
- no hay espacio suficiente para completar la operación proyectada;
- SQLite o directorios persistentes no pueden escribir.

En estado crítico:

- bloquear nuevas subidas;
- bloquear backups completos locales;
- seguir permitiendo eliminar contenidos no utilizados;
- seguir permitiendo descargar archivos;
- mantener funcionando el reproductor;
- mostrar instrucciones claras;
- no bloquear el login ni el acceso al panel.

La reserva de 15 GB debe considerarse una protección técnica. El administrador no debe poder reducirla accidentalmente por debajo de un mínimo seguro definido en código o infraestructura.

---

## 16. Comprobación previa a subidas

Antes de aceptar un archivo:

1. Obtener su tamaño desde el navegador cuando sea posible.
2. Consultar métricas recientes.
3. Calcular el espacio temporal necesario.
4. Considerar el original, miniatura y margen de seguridad.
5. Rechazar anticipadamente si se invade la reserva.
6. Volver a comprobar en el servidor antes de finalizar.

El cálculo debe contemplar que durante la subida puede coexistir un archivo temporal con el destino final.

Usar un factor conservador configurable, por ejemplo:

```env
SIMPLEVIEW_UPLOAD_SPACE_MULTIPLIER=2.1
```

Ejemplo:

```text
Archivo: 2 GB
Espacio temporal estimado: 4,2 GB
Reserva tras operación: debe seguir siendo ≥ 15 GB
```

Si el archivo ya existe por hash, reutilizarlo y no consumir una segunda copia física.

Los errores deben ser claros:

```text
No hay espacio suficiente para subir este archivo.
Libera al menos 4,8 GB o elimina contenidos que ya no se utilicen.
```

---

## 17. Dashboard de almacenamiento

Añadir al dashboard una tarjeta compacta con:

- porcentaje usado;
- espacio usado;
- espacio libre;
- estado: correcto, aviso o crítico;
- enlace `Gestionar almacenamiento`.

Crear una página específica con:

### Resumen

- capacidad total;
- usado;
- libre;
- reserva protegida;
- cuota de la aplicación, si se configura;
- fecha de última medición;
- evolución reciente.

### Desglose

- multimedia;
- backups;
- miniaturas;
- logs;
- temporales;
- Docker;
- sistema y otros.

### Recursos grandes

Lista de los archivos más grandes con:

- miniatura;
- nombre;
- tamaño;
- tipo;
- fecha de subida;
- número de usos;
- última utilización si existe;
- acción de revisar.

### Contenidos no utilizados

Mostrar recursos que:

- no estén en borradores;
- no estén en la publicación activa;
- no sean imagen de respaldo;
- no estén referenciados por copias necesarias.

Permitir selección múltiple y eliminación confirmada.

### Recomendaciones

Ejemplos:

```text
Hay 12 vídeos no utilizados que ocupan 8,4 GB.
Hay 3 copias completas antiguas que ocupan 6,1 GB.
La caché de compilación de Docker puede liberar 2,3 GB mediante mantenimiento técnico.
```

Las recomendaciones deben diferenciar:

- acciones disponibles para el cliente;
- acciones reservadas al técnico por SSH.

---

## 18. Limpieza segura

### 18.1 Automática permitida

Se permite automatizar únicamente tareas seguras:

- eliminar temporales incompletos con antigüedad superior al límite;
- aplicar retención de backups automáticos;
- rotar logs;
- eliminar miniaturas huérfanas regenerables;
- limpiar caché propia expirada;
- eliminar archivos marcados previamente para borrado y sin referencias.

### 18.2 No automática

No eliminar automáticamente:

- archivos multimedia válidos;
- publicación activa;
- borrador;
- backup más reciente;
- imágenes Docker activas;
- volúmenes Docker;
- archivos desconocidos del sistema.

No ejecutar `docker system prune -a` desde el panel.

Proporcionar un comando técnico separado y conservador, por ejemplo:

```text
scripts/docker-maintenance.sh
```

Debe:

- mostrar primero qué se puede recuperar;
- eliminar únicamente caché de compilación antigua o elementos no utilizados seguros;
- requerir confirmación explícita;
- no eliminar volúmenes;
- registrar la operación.

---

## 19. Backups y espacio

Antes de crear un backup:

- estimar su tamaño;
- comprobar espacio libre proyectado;
- aplicar la reserva;
- bloquear backups completos locales si no hay espacio;
- permitir backup de configuración si es seguro;
- recomendar USB, NAS o carpeta externa para backups completos.

La retención debe ejecutarse antes o después de la copia de manera que nunca elimine la última copia válida antes de confirmar que la nueva ha terminado correctamente.

Proceso seguro:

1. Comprobar espacio.
2. Crear la nueva copia temporal.
3. Validar checksum.
4. Marcarla como válida.
5. Aplicar retención sin eliminar la última copia anterior si la nueva falló.

---

## 20. Logs y temporales

### Logs

- rotación por tamaño o día;
- retención predeterminada de 14 días;
- compresión opcional;
- límite máximo documentado;
- no registrar payloads binarios;
- no registrar secretos.

### Temporales

- guardar en directorio persistente identificado;
- nombres aleatorios;
- registrar estado de la subida;
- limpiar subidas abandonadas;
- no mezclar temporales con multimedia válida;
- no dejar archivos parciales tras errores.

---

## 21. Despliegues y espacio

Actualizar `scripts/deploy.sh` para que antes de construir:

1. Ejecute una comprobación de almacenamiento.
2. Verifique espacio libre para Git, build y capas Docker.
3. Detenga el despliegue si se invade la reserva.
4. Muestre el espacio necesario y disponible.
5. Registre el resultado.
6. No borre datos persistentes.

Variable sugerida:

```env
SIMPLEVIEW_DEPLOY_MIN_FREE_GB=20
```

Si el despliegue falla por falta de espacio, debe conservar la versión actual funcionando.

---

## 22. Comandos y scripts

Implementar o mejorar:

```text
php artisan simpleview:storage-report
php artisan simpleview:storage-reconcile
php artisan simpleview:cleanup-storage
php artisan simpleview:find-orphans
php artisan simpleview:health-check
```

Scripts de host:

```text
scripts/storage-report.sh
scripts/docker-maintenance.sh
scripts/health-check.sh
scripts/deploy.sh
scripts/backup.sh
```

### `simpleview:storage-report`

Debe mostrar:

- total;
- usado;
- libre;
- reserva;
- estado;
- desglose;
- informe del host;
- fecha de medición.

Debe soportar salida humana y JSON.

### `simpleview:storage-reconcile`

Debe:

- comparar base de datos y archivos físicos;
- detectar huérfanos;
- detectar registros rotos;
- actualizar métricas;
- no borrar por defecto.

### `simpleview:cleanup-storage`

Por defecto debe ejecutarse en modo simulación.

Opciones sugeridas:

```text
--dry-run
--temp
--logs
--expired-backups
--orphan-thumbnails
--force
```

No debe borrar multimedia válida sin una opción explícita y confirmación.

---

## 23. Modelo de datos sugerido

Codex debe adaptar estos cambios al modelo existente y evitar tablas duplicadas.

### `storage_snapshots`

Campos sugeridos:

- id;
- filesystem_total_bytes;
- filesystem_used_bytes;
- filesystem_free_bytes;
- reserved_bytes;
- media_bytes;
- thumbnails_bytes;
- database_bytes;
- backups_bytes;
- logs_bytes;
- cache_bytes;
- temp_bytes;
- project_bytes;
- docker_bytes nullable;
- docker_reclaimable_bytes nullable;
- other_bytes nullable;
- status: ok | warning | critical | stale | error;
- source_measured_at;
- details_json nullable;
- created_at.

No conservar snapshots ilimitadamente.

Retención sugerida:

```text
48 muestras recientes de 5 minutos
30 muestras diarias
12 muestras mensuales
```

Puede simplificarse si el proyecto no necesita gráficos históricos, pero debe existir al menos el último estado válido.

### Campos adicionales de recursos

Aprovechar los existentes. Si faltan, considerar:

- `last_used_at`;
- `usage_count` calculado o cacheado;
- `deleted_at` para borrado seguro;
- `storage_verified_at`;
- `physical_exists` o estado equivalente.

No duplicar información que pueda calcularse de forma fiable.

---

## 24. API y servicios internos

Crear servicios de dominio claros, por ejemplo:

```text
StorageMetricsService
StoragePolicyService
UploadCapacityService
StorageCleanupService
StorageReconciliationService
HostStorageReportReader
```

Responsabilidades:

### `StorageMetricsService`

Obtiene métricas y desglose.

### `StoragePolicyService`

Determina estado y si una operación está permitida.

### `UploadCapacityService`

Calcula el espacio proyectado de una subida.

### `StorageCleanupService`

Ejecuta únicamente limpiezas seguras y auditables.

### `StorageReconciliationService`

Compara base de datos con disco.

### `HostStorageReportReader`

Lee y valida el JSON generado por el host.

No incluir lógica de políticas de espacio dispersa entre controladores y componentes de interfaz.

---

## 25. Seguridad

- No exponer rutas físicas completas al cliente.
- No exponer salida completa de comandos del host.
- No montar el socket Docker en la aplicación.
- Validar el JSON de métricas del host.
- Escribir informes de forma atómica.
- Evitar ataques de path traversal durante escaneos.
- No seguir enlaces simbólicos fuera de los directorios permitidos.
- Aplicar permisos mínimos.
- Auditar limpiezas y eliminaciones.
- Requerir confirmación para acciones destructivas.

---

# PARTE C — PLAN DE IMPLEMENTACIÓN

## 26. Estrategia para una versión estable

Trabajar en una rama específica:

```text
feature/phase-2-visual-editor-storage
```

Antes de migrar producción:

1. Crear backup de configuración y base de datos.
2. Registrar commit o tag estable actual.
3. Probar migraciones con una copia realista.
4. Verificar rollback de código.
5. Confirmar que la publicación actual sigue reproduciéndose durante el despliegue siempre que sea posible.

Usar una feature flag para el editor visual si el sistema existente permite hacerlo:

```env
SIMPLEVIEW_VISUAL_EDITOR_ENABLED=true
```

Durante validación puede mantenerse acceso temporal al editor anterior para el técnico, pero no duplicar indefinidamente dos flujos de negocio.

---

## 27. Orden recomendado

### Bloque 1 — Auditoría y contratos

- documentar estado actual;
- localizar servicios reutilizables;
- definir contratos del editor;
- definir política de almacenamiento;
- añadir pruebas de regresión.

### Bloque 2 — Medición y políticas de almacenamiento

Implementar primero las protecciones que evitarán llenar el disco durante las pruebas del editor:

- métricas;
- estado;
- preflight de subida;
- bloqueo crítico;
- script del host;
- tarjeta del dashboard;
- pruebas.

### Bloque 3 — Selector y lienzo

- selector visual;
- CSS Grid;
- estados de zona;
- selección;
- componentes.

### Bloque 4 — Biblioteca integrada y drag & drop

- biblioteca;
- filtros;
- arrastre;
- subida por zona;
- progreso;
- validación de capacidad.

### Bloque 5 — Inspector y autoguardado

- lista de zona;
- reordenación;
- configuración;
- guardado con debounce;
- recuperación de errores.

### Bloque 6 — Cambio de plantilla

- conservación;
- diálogo de reasignación;
- pruebas de no pérdida.

### Bloque 7 — Vista previa y publicación

- integración con mecanismos existentes;
- validaciones;
- comprobación crítica de disco;
- pruebas end-to-end.

### Bloque 8 — Limpieza, informes y documentación

- página completa de almacenamiento;
- recursos grandes;
- no utilizados;
- recomendaciones;
- scripts;
- README;
- guía de mantenimiento.

---

## 28. Pruebas requeridas

### Editor visual

- selector de plantilla;
- representación 16:9;
- cada plantilla genera las zonas correctas;
- soltar recurso en zona vacía;
- añadir a zona con contenido;
- selección múltiple;
- subida desde una zona;
- error de subida individual;
- reordenación;
- retirar sin borrar de biblioteca;
- autoguardado;
- fallo y reintento;
- cambio compatible de plantilla;
- reducción de zonas con confirmación;
- cancelación de cambio;
- vista previa;
- publicación;
- regresión del reproductor.

### Almacenamiento

- cálculo de métricas;
- informe desactualizado;
- umbral de aviso;
- umbral crítico;
- reserva mínima;
- cálculo proyectado de subida;
- rechazo previo;
- comprobación final en servidor;
- duplicado por hash no duplica espacio;
- bloqueo de backup completo;
- backup de configuración permitido cuando es seguro;
- detección de huérfanos;
- detección de archivos ausentes;
- limpieza de temporales;
- retención de backups;
- rotación de logs;
- fallo de lectura del informe del host;
- protección frente a symlinks y rutas no permitidas;
- `dry-run` no elimina archivos;
- despliegue se detiene de forma segura por falta de espacio.

### Navegadores

- Safari actual en macOS;
- Chrome actual en macOS;
- Chromium del OptiPlex para `/display`.

---

## 29. Criterios de aceptación

La fase se considerará completada cuando:

1. La versión estable actual siga funcionando después de migrar.
2. No se pierdan archivos, borradores ni publicación activa.
3. El usuario seleccione plantillas mediante tarjetas visuales.
4. El lienzo represente la televisión en 16:9.
5. Cada zona acepte imágenes y vídeos por arrastre.
6. Se pueda subir directamente desde una zona.
7. Añadir contenido no reemplace silenciosamente la lista.
8. La biblioteca permanezca accesible dentro del editor.
9. Las listas puedan reordenarse visualmente.
10. El borrador se guarde automáticamente.
11. Cambiar de plantilla no pierda contenidos sin confirmación.
12. La vista previa use el borrador y no afecte a la pantalla.
13. Publicar siga siendo una acción explícita.
14. El dashboard muestre capacidad, uso y espacio libre.
15. Exista un desglose por categorías.
16. Se muestren los archivos más grandes y contenidos no utilizados.
17. Las subidas se bloqueen antes de invadir la reserva.
18. La comprobación se repita en el servidor.
19. El sistema mantenga al menos 15 GB de reserva protegida.
20. Los backups completos locales se bloqueen si son inseguros.
21. Temporales, logs y backups tengan retención.
22. La aplicación no tenga acceso al socket Docker.
23. El informe del host se genere mediante script seguro.
24. El reproductor continúe funcionando en estado de almacenamiento crítico.
25. El panel siga permitiendo eliminar o descargar contenidos en estado crítico.
26. Las acciones de limpieza sean auditables y conservadoras.
27. Las pruebas automáticas relevantes pasen.
28. La documentación explique diagnóstico, limpieza y recuperación.

---

## 30. Entregables

Codex debe entregar:

- editor visual implementado;
- componentes documentados;
- arrastrar y soltar con alternativa por botones;
- biblioteca integrada;
- inspector de zona;
- autoguardado;
- cambio seguro de plantilla;
- integración con vista previa y publicación existentes;
- dashboard de almacenamiento;
- página de gestión de almacenamiento;
- servicios de métricas y políticas;
- preflight de subidas;
- reconciliación de disco y base de datos;
- limpieza segura;
- scripts de host;
- timer o cron documentado;
- migraciones;
- pruebas unitarias, integración y end-to-end;
- actualización de `.env.example`;
- actualización de `README.md`;
- actualización de documentación técnica;
- guía breve para el cliente;
- guía técnica para liberar espacio por SSH;
- plan y procedimiento de rollback.

---

## 31. Prompt operativo para iniciar Codex

Usa el siguiente mensaje junto con este archivo:

> Lee completamente `SIMPLE_VIEW_CODEX_CONTEXT.md` y `SIMPLE_VIEW_PHASE_2.md`. La aplicación ya tiene una versión estable; no la reescribas. Inspecciona primero el repositorio y genera un informe breve del estado actual, riesgos de compatibilidad, componentes reutilizables, migraciones necesarias y plan de implementación incremental. Añade pruebas de regresión antes de modificar los flujos críticos. Implementa primero las políticas de almacenamiento y el preflight de subidas; después desarrolla el editor visual centrado en un lienzo 16:9. Mantén borrador, vista previa, publicación y reproductor compatibles con el comportamiento existente. No montes el socket Docker dentro de la aplicación, no elimines archivos automáticamente salvo temporales, logs, cachés y backups conforme a políticas seguras, y no realices cambios destructivos en base de datos. Documenta cada desviación y ejecuta las pruebas tras cada bloque.

---

## 32. Principio final

La Fase 2 debe mejorar la facilidad de uso y la seguridad operativa sin convertir Simple View en una plataforma compleja.

El cliente debe sentir que está organizando directamente su televisión:

```text
Ve la plantilla → arrastra el contenido → comprueba el resultado → publica
```

El técnico debe poder confiar en que la aplicación no agotará silenciosamente el SSD:

```text
Mide → avisa → previene → permite limpiar de forma segura
```
