# Simple View — Contexto y especificación para Codex

## 1. Propósito del documento

Este archivo define el contexto funcional y técnico para desarrollar **Simple View**, una aplicación web local de cartelería digital orientada a un único cliente y una única pantalla.

Codex debe utilizar este documento como fuente principal de requisitos para generar la estructura del proyecto, implementar el MVP, crear los contenedores Docker, preparar los scripts de instalación y redactar la documentación de despliegue y mantenimiento.

Cuando exista ambigüedad, Codex debe:

1. Priorizar simplicidad de uso para el cliente final.
2. Priorizar estabilidad de reproducción.
3. Evitar dependencias innecesarias.
4. Mantener la aplicación funcional sin conexión a Internet.
5. Aplicar los valores predeterminados indicados en este documento.
6. Documentar cualquier desviación relevante antes de implementarla.

---

## 2. Resumen del proyecto

Simple View permitirá administrar desde un Mac los contenidos que se muestran en una televisión conectada a un Dell OptiPlex 7050 dentro de la misma red LAN.

El OptiPlex actuará simultáneamente como:

- servidor de la aplicación;
- almacenamiento de imágenes y vídeos;
- reproductor de cartelería digital;
- host Docker;
- equipo conectado físicamente a la televisión.

El Mac solo se utilizará para abrir el panel de administración desde un navegador.

### Flujo básico esperado

1. El administrador accede a `http://simpleview.local/admin`.
2. Sube imágenes o vídeos mediante arrastrar y soltar.
3. Selecciona una plantilla de pantalla.
4. Añade contenidos a cada zona.
5. Ordena las listas de reproducción.
6. Abre una vista previa en una ventana nueva.
7. Publica los cambios.
8. La pantalla conectada al OptiPlex actualiza el contenido automáticamente.

---

## 3. Objetivos principales

- Permitir que una persona sin formación técnica publique contenidos desde el primer minuto.
- Reducir el flujo de trabajo a **subir, colocar, previsualizar y publicar**.
- Reproducir principalmente fotografías y vídeos MP4.
- Permitir varias distribuciones de pantalla dividida.
- Hacer que cada zona reproduzca su propia lista de forma independiente.
- Mantener la última publicación válida tras reinicios o fallos.
- Funcionar únicamente en red local durante el MVP.
- Desplegar la aplicación mediante Docker Compose.
- Evitar instalaciones nativas de PHP, Node.js, bases de datos o dependencias del proyecto.
- Arrancar automáticamente después de encender o reiniciar el OptiPlex.
- Facilitar actualizaciones posteriores mediante Git y scripts de despliegue.

---

## 4. Alcance del MVP

### Incluido

- Una sola instalación.
- Una sola pantalla física.
- Un único usuario administrador.
- Panel de administración en español.
- Autenticación sencilla.
- Biblioteca multimedia.
- Imágenes y vídeos MP4.
- Listas mezcladas de imágenes y vídeos en cualquier zona.
- Plantillas de pantalla predefinidas.
- Edición en borrador.
- Vista previa independiente.
- Publicación manual.
- Reproductor en modo kiosco.
- Horario semanal de reproducción.
- Imagen de respaldo.
- Estado del reproductor.
- Gestión básica del almacenamiento.
- Copias de seguridad sencillas y programables.
- Despliegue con Docker Compose.
- Scripts de instalación, despliegue, copia y restauración.

### Fuera del MVP

- Gestión de múltiples clientes o instalaciones.
- Gestión de múltiples pantallas.
- Acceso desde Internet.
- Aplicaciones móviles.
- Conversión automática de vídeo.
- Soporte para PowerPoint.
- Soporte para PDF como contenido reproducible.
- Feeds RSS.
- Redes sociales.
- Audio.
- Editor libre de diseño por arrastrar y soltar zonas.
- Historial completo de publicaciones.
- Programación de campañas complejas.
- Roles y permisos múltiples.
- Analítica avanzada.
- Métricas de reproducción certificadas.
- Control físico de encendido o apagado de la televisión.

---

## 5. Entorno de hardware

### Host

- Modelo: Dell OptiPlex 7050.
- Sistema operativo objetivo: Ubuntu 24.04 LTS.
- Disco disponible estimado: entre 128 GB y 256 GB.
- Conexión recomendada: Ethernet.
- El equipo estará conectado directamente a la televisión.
- El equipo deberá permanecer sin suspensión durante el horario configurado.

### Pantalla

- Televisión Full HD o 4K.
- Orientación horizontal para el MVP.
- La interfaz debe ser adaptable a 1920 × 1080 y 3840 × 2160.
- Reproducción sin sonido.
- El reproductor debe funcionar correctamente aunque la televisión cambie entre Full HD y 4K.

---

## 6. Arquitectura propuesta

### Stack recomendado

- Backend: Laravel.
- Panel administrativo: Filament o componentes equivalentes integrados en Laravel.
- Reproductor: Blade, Alpine.js y JavaScript nativo, evitando un SPA pesado.
- Base de datos: SQLite.
- Servidor web: Nginx.
- Runtime PHP: PHP-FPM.
- Procesamiento multimedia: FFmpeg y ffprobe únicamente para inspección y generación de miniaturas.
- Contenedores: Docker Compose.
- Navegador kiosco: Chromium instalado en el host Ubuntu.
- Persistencia: bind mounts o volúmenes Docker para base de datos, archivos multimedia, miniaturas, copias y configuración.

Codex puede modificar detalles internos del stack si mantiene los objetivos de simplicidad, bajo consumo y facilidad de mantenimiento. No debe introducir Redis, PostgreSQL, Kubernetes, colas distribuidas ni microservicios salvo necesidad técnica demostrable.

### Contenedores mínimos

1. `app`
   - Laravel.
   - PHP-FPM.
   - Comandos Artisan.
   - FFmpeg/ffprobe.

2. `web`
   - Nginx.
   - Expone la aplicación en la LAN.

No es obligatorio separar un worker en el MVP. Si se usan trabajos en segundo plano para miniaturas, se puede usar la cola `database` o `sync`. Se debe preferir la opción más sencilla y fiable.

### Comunicación reproductor-servidor

Para el MVP se acepta sondeo periódico:

- El reproductor consulta cada 2–3 segundos el identificador o versión de la publicación activa.
- Si la versión cambia, descarga la nueva configuración y actualiza la presentación.
- No es necesario WebSocket en la primera versión.
- El mecanismo debe evitar recargar archivos no modificados.

---

## 7. Rutas principales

### Administración

- `/admin`
  - Inicio de sesión.
  - Dashboard.
  - Biblioteca multimedia.
  - Editor de pantalla.
  - Vista previa.
  - Configuración.
  - Copias de seguridad.

### Reproductor

- `/display`
  - Ruta utilizada por Chromium en modo kiosco.
  - No debe mostrar controles administrativos.
  - Debe ocupar toda la ventana.
  - Debe ocultar cursor tras un periodo de inactividad.
  - Debe bloquear scroll y selección de texto.

### Vista previa

- `/preview`
  - Abre una nueva ventana desde el Mac.
  - Representa el borrador actual, no la publicación activa.
  - Reproduce vídeos reales.
  - No modifica la pantalla física.
  - Debe mantener la proporción 16:9.

### API interna sugerida

- `GET /api/display/config`
- `GET /api/display/version`
- `POST /api/display/heartbeat`
- `POST /api/display/error`
- `GET /api/preview/config`
- `POST /api/publish`

Las rutas exactas pueden cambiar, pero debe existir una separación clara entre administración, vista previa y reproducción.

---

## 8. Autenticación y acceso

- Un único usuario administrador.
- Login mediante correo o nombre de usuario y contraseña.
- El usuario se crea durante la instalación inicial.
- Las credenciales iniciales se leen desde `.env`.
- En el primer inicio de sesión se debe solicitar cambio de contraseña.
- Las sesiones deben usar cookies seguras y protección CSRF.
- El panel solo debe estar disponible en la LAN.
- No se implementará recuperación de contraseña por correo.
- Debe existir un comando de consola para restablecer la contraseña.

Variables sugeridas:

```env
SIMPLEVIEW_ADMIN_EMAIL=admin@simpleview.local
SIMPLEVIEW_ADMIN_PASSWORD=change-me
SIMPLEVIEW_FORCE_PASSWORD_CHANGE=true
```

---

## 9. Experiencia de usuario

### Principios

- Diseño limpio y sencillo.
- Navegación con pocos apartados.
- Etiquetas claras en español.
- Evitar vocabulario técnico como campañas, nodos, datasets o endpoints.
- Acciones principales siempre visibles.
- Confirmaciones antes de acciones destructivas.
- Mensajes de error comprensibles.
- Debe ser usable desde Safari o Chrome en macOS.

### Menú recomendado

- Inicio.
- Editar pantalla.
- Contenidos.
- Horarios.
- Configuración.
- Copias de seguridad.
- Cerrar sesión.

### Dashboard

Debe mostrar:

- Estado del reproductor.
- Estado actual: reproduciendo, fuera de horario, sin conexión o error.
- Miniatura o representación del diseño publicado.
- Botón `Editar pantalla`.
- Botón `Vista previa`.
- Botón `Publicar`.
- Espacio utilizado y disponible.
- Próximo cambio de horario.
- Fecha de última copia de seguridad.
- Avisos pendientes.

---

## 10. Plantillas de pantalla

Las plantillas tendrán proporciones fijas en el MVP.

### Plantilla 1: pantalla completa

```text
┌──────────────────────────────┐
│                              │
│            Zona 1            │
│                              │
└──────────────────────────────┘
```

### Plantilla 2: dos columnas iguales

```text
┌───────────────┬──────────────┐
│               │              │
│    Zona 1     │    Zona 2    │
│               │              │
└───────────────┴──────────────┘
```

### Plantilla 3: dos filas iguales

```text
┌──────────────────────────────┐
│            Zona 1            │
├──────────────────────────────┤
│            Zona 2            │
└──────────────────────────────┘
```

### Plantilla 4: cuadrícula 2 × 2

```text
┌───────────────┬──────────────┐
│    Zona 1     │    Zona 2    │
├───────────────┼──────────────┤
│    Zona 3     │    Zona 4    │
└───────────────┴──────────────┘
```

### Plantilla 5: dos medios y lateral derecho

Proporción aproximada: 70/30.

```text
┌────────────────────┬────────┐
│       Zona 1       │        │
├────────────────────┤ Zona 3 │
│       Zona 2       │        │
└────────────────────┴────────┘
```

### Plantilla 6: lateral izquierdo y dos medios

Proporción aproximada: 30/70.

```text
┌────────┬────────────────────┐
│        │       Zona 1       │
│ Zona 3 ├────────────────────┤
│        │       Zona 2       │
└────────┴────────────────────┘
```

Las plantillas deben implementarse con CSS Grid y ser totalmente responsivas dentro de una proporción 16:9.

---

## 11. Modelo de edición y publicación

Debe haber dos estados separados:

### Borrador

- Editable desde el panel.
- Visible en `/preview`.
- No afecta al reproductor.

### Publicado

- Configuración activa utilizada por `/display`.
- Solo cambia cuando el administrador pulsa `Publicar`.
- Debe ser una instantánea inmutable de la configuración del borrador en ese momento.

### Publicación

Al pulsar `Publicar`:

1. Validar que todas las zonas tengan una configuración válida.
2. Validar que todos los archivos existan.
3. Crear una nueva versión publicada.
4. Incrementar `publication_version`.
5. Registrar fecha y usuario.
6. El reproductor detectará la nueva versión en un máximo de 3 segundos.
7. El cambio será inmediato, aunque interrumpa el contenido actual.

No se requiere historial completo en el MVP, pero se recomienda conservar al menos la publicación activa anterior internamente para recuperación automática ante un error de publicación.

---

## 12. Zonas y listas de reproducción

Cada zona debe contener una lista ordenada de elementos multimedia.

### Tipos admitidos

- Imagen.
- Vídeo MP4.

### Reglas

- Cualquier zona puede contener una lista mezclada.
- Los elementos se pueden reordenar mediante arrastrar y soltar.
- Las zonas se reproducen de forma independiente.
- Las zonas no necesitan estar sincronizadas.
- Varias zonas pueden reproducir vídeo al mismo tiempo.
- Todo el audio debe estar silenciado.
- Al finalizar la lista, se vuelve al primer elemento.
- Una zona con un solo vídeo lo reproduce en bucle.
- Una zona con una sola imagen la mantiene visible.

### Imágenes

Cada imagen debe permitir:

- duración individual;
- modo `cover`;
- modo `contain`;
- transición al siguiente elemento.

Valores predeterminados:

- duración: 10 segundos;
- ajuste: `cover`;
- transición: fundido;
- duración del fundido: 500 ms.

### Vídeos

- Formato principal: MP4.
- Reproducción completa antes de pasar al siguiente elemento.
- Silenciados con `muted`.
- Reproducción automática con `autoplay`.
- Reproducción en línea con `playsinline`.
- No mostrar controles.
- Si falla el vídeo, registrar error y pasar al siguiente elemento.

---

## 13. Transiciones

El MVP debe incluir:

- corte directo;
- fundido.

Duraciones permitidas para fundido:

- 250 ms;
- 500 ms;
- 1000 ms.

Valor predeterminado: 500 ms.

Las transiciones no deben provocar parpadeos, fondo blanco ni pérdida temporal de la proporción de la zona.

---

## 14. Biblioteca multimedia

### Funciones obligatorias

- Subida mediante arrastrar y soltar.
- Selección múltiple.
- Barra de progreso por archivo.
- Miniaturas.
- Nombre descriptivo editable.
- Nombre original del archivo.
- Tipo de archivo.
- Tamaño.
- Resolución.
- Duración para vídeos.
- Fecha de subida.
- Descargar original.
- Eliminar.
- Buscar por nombre.
- Filtrar por tipo.
- Mostrar si el archivo está en uso.

### Subida por sección

Desde el editor de una zona debe ser posible:

- seleccionar archivos existentes;
- subir nuevos archivos sin abandonar el editor;
- añadir inmediatamente los archivos subidos a la zona actual.

### Duplicados

- Calcular hash SHA-256 del archivo.
- Si ya existe el mismo archivo, reutilizar el registro existente.
- No almacenar dos copias físicas idénticas.
- Permitir usar el mismo recurso en múltiples zonas.

### Eliminación

- Si el archivo está siendo utilizado, bloquear la eliminación directa.
- Mostrar en qué borradores o publicaciones se usa.
- Permitir retirarlo de las zonas antes de eliminarlo.
- La eliminación física debe realizarse de forma segura.

---

## 15. Validación multimedia

### Imágenes admitidas

- JPEG.
- PNG.
- WebP.

Se puede añadir GIF en una fase posterior.

### Vídeos admitidos

- Extensión `.mp4`.
- Contenedor MP4 válido.
- Códec de vídeo recomendado: H.264.
- Audio permitido en el archivo, pero siempre silenciado durante la reproducción.

### Verificación

Usar `ffprobe` para obtener:

- códec;
- duración;
- resolución;
- bitrate;
- existencia de pista de vídeo;
- validez del contenedor.

Si un MP4 no es compatible:

- rechazarlo;
- explicar el motivo;
- sugerir exportarlo como MP4/H.264;
- no convertirlo automáticamente en el MVP.

### Miniaturas

- Imágenes: crear versión optimizada.
- Vídeos: capturar un fotograma representativo con FFmpeg.
- No procesar el archivo original de forma destructiva.

---

## 16. Límites de subida y almacenamiento

### Valores iniciales

- Tamaño máximo por archivo: 2 GB.
- Límite técnico absoluto configurable en infraestructura: 4 GB.
- El límite funcional debe poder reducirse desde Configuración.
- Reservar al menos 15 GB para el sistema operativo.

### Avisos

- Aviso al alcanzar el 80 % del espacio asignado.
- Bloqueo de nuevas subidas al alcanzar el 90 %.
- Mostrar espacio total, usado y disponible.
- Mostrar los archivos de mayor tamaño.

### Consideraciones de implementación

El servidor web, PHP y la aplicación deben permitir cargas grandes sin agotar memoria. Las subidas deben procesarse por streaming o mediante archivos temporales, nunca cargando el fichero completo en RAM.

---

## 17. Horario semanal

El sistema debe permitir configurar un horario general de reproducción.

### Requisitos

- Configuración independiente para cada día de la semana.
- Posibilidad de marcar un día como cerrado.
- Hasta dos intervalos por día.
- Zona horaria configurable, por defecto `Europe/Madrid`.
- Botón para activar temporalmente la reproducción fuera de horario.
- Botón para pausar temporalmente la reproducción.

### Fuera del horario

- Detener vídeos.
- Detener el avance de listas.
- Liberar recursos de reproducción cuando sea posible.
- Mostrar una imagen de respaldo o fondo negro.
- Seguir enviando heartbeat al servidor.
- Reanudar automáticamente al comenzar el siguiente intervalo.

### Contenido fuera del horario

Configuración seleccionable:

- logotipo o imagen corporativa;
- fondo negro.

Valor predeterminado: imagen corporativa.

El sistema no controla físicamente el encendido o apagado de la televisión.

---

## 18. Imagen de respaldo

El administrador debe poder subir una imagen corporativa utilizada cuando:

- no existe una publicación válida;
- el sistema está fuera de horario;
- una zona no tiene contenido;
- ocurre un error grave de reproducción;
- la aplicación acaba de arrancar y aún no ha cargado la configuración.

Si no existe imagen configurada, mostrar fondo negro.

---

## 19. Reproductor

### Comportamiento

- Pantalla completa.
- Fondo negro.
- Sin barras de desplazamiento.
- Sin controles visibles.
- Sin audio.
- Recuperación automática tras errores.
- Mantener la última configuración válida en caché local.
- Si el servidor no está disponible temporalmente, continuar con la última publicación conocida.
- Reintentar conexión automáticamente.
- No mostrar mensajes técnicos al público.

### Heartbeat

El reproductor debe informar periódicamente:

- estado en línea;
- versión publicada cargada;
- hora local;
- resolución detectada;
- estado de horario;
- archivo en reproducción por zona;
- errores recientes.

Frecuencia sugerida: cada 5 segundos.

Considerar desconectado al reproductor tras 15 segundos sin heartbeat.

### Estados administrativos

- En línea.
- Fuera de horario.
- Sin conexión.
- Error de reproducción.

### Errores

Si un archivo falla:

1. registrar el error;
2. marcar el recurso como problemático;
3. avanzar al siguiente elemento;
4. evitar que la zona quede bloqueada;
5. mostrar aviso en el panel;
6. continuar reproduciendo el resto del contenido.

---

## 20. Vista previa

- Abrirse en una ventana o pestaña nueva.
- Tener tamaño reducido, pero ajustable por el usuario.
- Mostrar proporción 16:9.
- Utilizar el borrador actual.
- Reproducir realmente imágenes y vídeos.
- Mantener las zonas independientes.
- No enviar heartbeats como reproductor físico.
- Mostrar una etiqueta discreta `Vista previa`.
- Tener controles opcionales para reiniciar la simulación.

---

## 21. Modelo de datos sugerido

Codex puede ajustar nombres y relaciones, pero debe preservar el significado.

### `users`

- id
- name
- email
- password
- must_change_password
- created_at
- updated_at

### `media_assets`

- id
- display_name
- original_filename
- storage_path
- thumbnail_path
- mime_type
- media_type: image | video
- extension
- file_size
- sha256
- width
- height
- duration_ms nullable
- video_codec nullable
- status: processing | ready | invalid | error
- validation_message nullable
- created_at
- updated_at

### `layouts`

- id
- name
- template_key
- state: draft | published | archived
- version
- published_at nullable
- created_at
- updated_at

### `layout_zones`

- id
- layout_id
- zone_key
- position
- image_fit_default: cover | contain
- image_duration_default_ms
- transition_type: cut | fade
- transition_duration_ms
- created_at
- updated_at

### `playlist_items`

- id
- layout_zone_id
- media_asset_id
- sort_order
- image_duration_ms nullable
- image_fit nullable
- created_at
- updated_at

### `settings`

Puede ser tabla clave-valor tipada o un modelo estructurado. Debe almacenar como mínimo:

- application_name
- timezone
- default_image_duration_ms
- default_image_fit
- default_transition_type
- default_transition_duration_ms
- max_upload_size_bytes
- storage_warning_percentage
- storage_block_percentage
- fallback_media_asset_id
- after_hours_mode
- active_publication_version

### `business_hours`

- id
- weekday
- is_closed
- first_start nullable
- first_end nullable
- second_start nullable
- second_end nullable
- created_at
- updated_at

### `display_status`

- id
- display_key
- last_seen_at
- loaded_publication_version
- screen_width
- screen_height
- state
- current_items_json nullable
- last_error nullable
- updated_at

### `playback_errors`

- id
- media_asset_id nullable
- zone_key nullable
- publication_version nullable
- message
- context_json nullable
- occurred_at
- resolved_at nullable

### `backups`

- id
- filename
- path
- type: configuration | full
- size
- status
- started_at
- completed_at nullable
- error_message nullable

---

## 22. Copias de seguridad

### Funcionalidad mínima

- Copia automática diaria de base de datos y configuración.
- Hora predeterminada: 03:00.
- Conservar las últimas 7 copias automáticas.
- Botón `Crear copia ahora`.
- Botón `Descargar copia`.
- Mostrar estado de la última copia.
- Permitir exportación manual completa incluyendo multimedia.

### Tipos

#### Copia de configuración

Incluye:

- SQLite;
- configuración;
- horarios;
- diseños;
- listas;
- referencia a archivos;
- imagen de respaldo.

#### Copia completa

Incluye además:

- imágenes originales;
- vídeos originales;
- miniaturas.

### Destino

El MVP debe soportar almacenamiento local en un directorio persistente. La documentación debe explicar cómo montar opcionalmente un USB, NAS o carpeta compartida como destino de copias.

---

## 23. Persistencia y estructura de directorios

Estructura sugerida en el host:

```text
/opt/simple-view/
├── app/
├── data/
│   ├── database/
│   ├── media/
│   ├── thumbnails/
│   ├── backups/
│   ├── logs/
│   └── cache/
├── scripts/
├── .env
└── docker-compose.yml
```

Los datos deben sobrevivir a:

- recreación de contenedores;
- actualización del código;
- reinicio del sistema;
- reconstrucción de imágenes Docker.

Nunca guardar datos persistentes únicamente dentro de la capa efímera de un contenedor.

---

## 24. Docker Compose

### Requisitos

- `restart: unless-stopped` o equivalente.
- Healthchecks para los servicios principales.
- Variables de entorno documentadas.
- Sin secretos incluidos en el repositorio.
- Red interna entre contenedores.
- Exponer únicamente el puerto necesario a la LAN.
- Soportar arquitectura x86_64.
- Construcción reproducible.
- Migraciones automáticas controladas durante el despliegue.

### Comandos esperados

```bash
docker compose build
docker compose up -d
docker compose ps
docker compose logs -f
docker compose exec app php artisan migrate --force
```

---

## 25. Scripts requeridos

### `scripts/provision-host.sh`

Objetivo: preparar un Ubuntu 24.04 LTS recién instalado.

Debe:

- verificar que se ejecuta como root o mediante sudo;
- actualizar paquetes;
- instalar Docker Engine;
- instalar Docker Compose Plugin;
- instalar Git;
- instalar Chromium;
- crear usuario restringido `display`;
- configurar inicio de sesión automático del usuario `display`;
- desactivar suspensión, bloqueo y salvapantallas;
- configurar Chromium en modo kiosco;
- configurar arranque automático de Docker;
- crear `/opt/simple-view` y directorios persistentes;
- configurar permisos mínimos necesarios;
- generar o copiar un `.env.example`;
- levantar la aplicación;
- configurar acceso local mediante `simpleview.local` cuando sea posible;
- mostrar un resumen final y pasos manuales pendientes.

El script debe ser idempotente en la medida de lo posible.

### `scripts/deploy.sh`

Debe:

1. validar que no existan cambios locales sin confirmar;
2. guardar referencia de la versión desplegada;
3. ejecutar `git pull` o checkout de una versión indicada;
4. construir imágenes;
5. ejecutar migraciones;
6. levantar servicios;
7. comprobar healthchecks;
8. restaurar la versión previa si falla;
9. registrar el despliegue.

### `scripts/rollback.sh`

- volver al commit o tag anterior;
- reconstruir y levantar servicios;
- advertir sobre incompatibilidades de migraciones;
- no destruir datos.

### `scripts/backup.sh`

- crear copia consistente de SQLite;
- incluir configuración;
- permitir opción `--full` para incluir multimedia;
- aplicar retención;
- generar checksum;
- registrar resultado.

### `scripts/restore.sh`

- validar checksum;
- detener aplicación de forma segura;
- crear copia preventiva del estado actual;
- restaurar datos;
- ajustar permisos;
- volver a levantar servicios;
- ejecutar comprobación de salud.

### `scripts/health-check.sh`

- verificar respuesta HTTP;
- verificar acceso a SQLite;
- verificar directorios persistentes;
- verificar espacio libre;
- verificar estado de contenedores;
- mostrar salida clara y código de retorno adecuado.

---

## 26. Modo kiosco

Chromium debe iniciarse automáticamente con una configuración equivalente a:

```bash
chromium \
  --kiosk \
  --noerrdialogs \
  --disable-infobars \
  --disable-session-crashed-bubble \
  --autoplay-policy=no-user-gesture-required \
  http://127.0.0.1/display
```

El mecanismo concreto puede ser:

- autostart del entorno de escritorio;
- servicio systemd de usuario;
- sesión gráfica mínima.

Requisitos:

- iniciar automáticamente tras reinicio;
- reabrir Chromium si se cierra;
- ocultar el escritorio al público;
- evitar notificaciones del sistema;
- impedir suspensión automática;
- mantener acceso técnico por SSH.

---

## 27. Red local

- El panel debe ser accesible mediante IP privada.
- Nombre deseado: `http://simpleview.local/admin`.
- El reproductor debe usar preferentemente `http://127.0.0.1/display`.
- No abrir puertos en el router.
- No depender de Internet.
- Documentar reserva DHCP o IP fija.
- Documentar alternativa si mDNS no funciona en la red.

---

## 28. Seguridad

- Acceso administrativo autenticado.
- CSRF habilitado.
- Cookies `HttpOnly` y `SameSite`.
- Validación estricta de archivos.
- No confiar en extensión o MIME enviados por el navegador.
- Nombres físicos generados por la aplicación.
- Evitar ejecución de archivos subidos.
- Directorio multimedia sin permisos de ejecución.
- Protección contra path traversal.
- Rate limiting en login.
- Registro de intentos fallidos.
- Contraseñas con hash seguro.
- No exponer `.env`.
- No ejecutar contenedores como root salvo necesidad justificada.
- Firewall del host limitado a SSH y puerto web en la LAN.

El MVP puede funcionar por HTTP dentro de la LAN. La documentación debe explicar cómo añadir HTTPS local en el futuro.

---

## 29. Rendimiento

### Objetivos

- Panel fluido desde el Mac.
- Reproducción sin saltos visibles en Full HD.
- Soporte razonable para 4K dependiendo del códec y hardware.
- No cargar vídeos completos en memoria.
- Servir archivos con soporte de `Range Requests`.
- Cachear recursos estáticos.
- Precargar el siguiente elemento cuando sea posible.
- Evitar recargar vídeos que ya estén en caché.
- Generar miniaturas en segundo plano o de forma controlada.

### Consideraciones

La reproducción simultánea de varios vídeos 4K puede superar la capacidad del hardware. La interfaz debe mostrar una advertencia si se asignan múltiples vídeos 4K a diferentes zonas. El MVP no necesita impedirlo de forma absoluta, pero debe informar al administrador.

---

## 30. Registro y observabilidad

Registrar como mínimo:

- inicios y cierres de sesión;
- subidas y eliminaciones;
- publicación de diseños;
- errores de validación multimedia;
- errores de reproducción;
- copias de seguridad;
- despliegues y migraciones;
- espacio insuficiente.

Los logs no deben crecer indefinidamente. Implementar rotación o retención.

El panel solo debe mostrar mensajes útiles para el cliente. Los detalles técnicos deben permanecer en logs accesibles por SSH.

---

## 31. Comandos Artisan sugeridos

- `simpleview:create-admin`
- `simpleview:reset-admin-password`
- `simpleview:health-check`
- `simpleview:backup`
- `simpleview:cleanup-backups`
- `simpleview:storage-report`
- `simpleview:validate-media`

---

## 32. Pruebas

### Pruebas unitarias

- cálculo de horario activo;
- validación de intervalos;
- orden de listas;
- cálculo de espacio utilizado;
- detección de duplicados;
- generación de configuración publicada;
- selección de imagen de respaldo;
- transición entre elementos.

### Pruebas de integración

- login;
- subida de imagen;
- subida de MP4 válido;
- rechazo de MP4 inválido;
- publicación;
- consulta de versión;
- heartbeat;
- bloqueo de eliminación de archivo en uso;
- creación y restauración de copia.

### Pruebas end-to-end

- crear borrador;
- seleccionar plantilla;
- añadir contenidos a varias zonas;
- reordenar elementos;
- abrir vista previa;
- publicar;
- comprobar actualización del reproductor;
- comprobar reproducción fuera de horario;
- simular servidor temporalmente inaccesible;
- comprobar continuidad con última publicación válida.

### Navegadores

- Safari actual en macOS para administración.
- Chrome actual en macOS para administración.
- Chromium en Ubuntu para reproducción.

---

## 33. Criterios de aceptación del MVP

El MVP se considera aceptado cuando se cumplan todos los puntos siguientes:

1. La aplicación se instala en Ubuntu 24.04 mediante el script documentado.
2. Docker Compose levanta todos los servicios automáticamente.
3. Después de reiniciar el OptiPlex, la aplicación y Chromium arrancan sin intervención.
4. El administrador accede desde el Mac por la LAN.
5. El administrador puede iniciar sesión.
6. Puede subir varias imágenes y vídeos MP4.
7. Se generan miniaturas.
8. Puede crear un diseño usando cualquiera de las plantillas definidas.
9. Cada zona acepta listas mezcladas.
10. Los elementos se reordenan por arrastrar y soltar.
11. Las imágenes permiten `cover` y `contain`.
12. La duración de las imágenes es configurable.
13. Los vídeos se reproducen completos y sin sonido.
14. Las zonas funcionan de manera independiente.
15. La vista previa se abre en una nueva ventana y no afecta a la pantalla.
16. La publicación actualiza la pantalla en un máximo de 3 segundos.
17. El reproductor continúa mostrando la última publicación si pierde temporalmente conexión con el backend.
18. Fuera de horario se detiene la reproducción y se muestra el respaldo configurado.
19. Un archivo dañado no bloquea la zona.
20. El panel muestra el estado del reproductor.
21. Se muestran avisos de almacenamiento.
22. Se puede crear y descargar una copia de seguridad.
23. Los datos sobreviven a la recreación de contenedores.
24. La documentación explica instalación, actualización, copia, restauración y resolución de problemas.

---

## 34. Fases de implementación recomendadas

### Fase 1: base del proyecto

- Laravel.
- Filament.
- Docker Compose.
- SQLite.
- Login.
- Configuración inicial.
- Healthchecks.

### Fase 2: biblioteca multimedia

- subida;
- validación;
- hash;
- miniaturas;
- descarga;
- eliminación segura;
- almacenamiento.

### Fase 3: editor de pantalla

- plantillas;
- zonas;
- listas;
- ordenación;
- configuración de imágenes;
- borrador.

### Fase 4: reproductor y vista previa

- motor de reproducción;
- bucles independientes;
- transiciones;
- caché local;
- errores;
- vista previa.

### Fase 5: publicación y estado

- snapshot publicado;
- versión;
- sondeo;
- heartbeat;
- dashboard.

### Fase 6: horarios y respaldo

- calendario semanal;
- modo fuera de horario;
- overrides temporales;
- imagen corporativa.

### Fase 7: copias y despliegue

- backups;
- restore;
- scripts;
- modo kiosco;
- documentación.

### Fase 8: endurecimiento

- pruebas;
- seguridad;
- rendimiento;
- manejo de disco lleno;
- recuperación ante fallos.

---

## 35. Entregables esperados de Codex

- Código fuente completo.
- `README.md`.
- `.env.example`.
- `docker-compose.yml`.
- Dockerfiles necesarios.
- Configuración Nginx.
- Migraciones y seeders.
- Usuario administrador inicial.
- Panel administrativo.
- Reproductor.
- Vista previa.
- Pruebas automáticas.
- Scripts de `provision`, `deploy`, `rollback`, `backup`, `restore` y `health-check`.
- Documentación de instalación en Ubuntu 24.04.
- Documentación de actualización mediante Git.
- Documentación de recuperación tras fallos.
- Guía breve de uso para el cliente final.

---

## 36. Variables de entorno sugeridas

```env
APP_NAME="Simple View"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://simpleview.local
APP_TIMEZONE=Europe/Madrid

SIMPLEVIEW_ADMIN_EMAIL=admin@simpleview.local
SIMPLEVIEW_ADMIN_PASSWORD=change-me
SIMPLEVIEW_FORCE_PASSWORD_CHANGE=true

SIMPLEVIEW_DISPLAY_KEY=main-display
SIMPLEVIEW_POLL_INTERVAL_MS=2500
SIMPLEVIEW_HEARTBEAT_INTERVAL_MS=5000
SIMPLEVIEW_OFFLINE_THRESHOLD_SECONDS=15

SIMPLEVIEW_MAX_UPLOAD_MB=2048
SIMPLEVIEW_MAX_UPLOAD_HARD_MB=4096
SIMPLEVIEW_STORAGE_WARNING_PERCENT=80
SIMPLEVIEW_STORAGE_BLOCK_PERCENT=90
SIMPLEVIEW_STORAGE_RESERVE_GB=15

SIMPLEVIEW_DEFAULT_IMAGE_DURATION_MS=10000
SIMPLEVIEW_DEFAULT_IMAGE_FIT=cover
SIMPLEVIEW_DEFAULT_TRANSITION=fade
SIMPLEVIEW_DEFAULT_TRANSITION_DURATION_MS=500

SIMPLEVIEW_BACKUP_SCHEDULE="0 3 * * *"
SIMPLEVIEW_BACKUP_RETENTION_DAYS=7
SIMPLEVIEW_BACKUP_PATH=/data/backups
```

---

## 37. Decisiones predeterminadas pendientes de validación

Estas decisiones se consideran aceptadas para comenzar el desarrollo, salvo instrucción posterior:

- orientación horizontal;
- plantillas con proporciones fijas;
- incluir dos columnas y dos filas;
- incluir lateral a izquierda y derecha;
- proporción lateral 30/70;
- duración de imagen predeterminada de 10 segundos;
- fundido de 500 ms;
- cambio inmediato al publicar;
- horario distinto por día con hasta dos intervalos;
- imagen corporativa fuera de horario;
- heartbeat cada 5 segundos;
- pantalla desconectada tras 15 segundos;
- copia diaria a las 03:00;
- conservación de 7 copias;
- mantenimiento técnico por SSH;
- repositorio Git privado;
- despliegue dirigido a Ubuntu 24.04 LTS;
- acceso administrativo solo desde LAN;
- sin audio;
- sin conversión automática de vídeo.

---

## 38. Instrucción inicial sugerida para Codex

Usar el siguiente mensaje junto con este archivo:

> Lee completamente `SIMPLE_VIEW_CODEX_CONTEXT.md`. Genera primero un plan de implementación por fases y una propuesta de estructura del repositorio. Después crea el esqueleto funcional de la Fase 1 con Laravel, Filament, SQLite y Docker Compose. No implementes dependencias no indicadas sin justificarlo. Mantén todos los datos persistentes fuera de los contenedores. Incluye pruebas, healthchecks, `.env.example` y documentación para Ubuntu 24.04 LTS. Antes de avanzar a cada fase, verifica los criterios de aceptación relacionados.

---

## 39. Principio final

Simple View no debe intentar competir con una plataforma generalista de cartelería digital. Debe resolver muy bien un escenario concreto:

> Una persona administra desde su Mac una televisión conectada a un mini PC dentro de la misma red, subiendo fotografías y vídeos, organizándolos en zonas y publicándolos con la menor complejidad posible.

Cualquier funcionalidad que complique significativamente este flujo debe posponerse salvo que sea imprescindible para estabilidad, seguridad o mantenimiento.