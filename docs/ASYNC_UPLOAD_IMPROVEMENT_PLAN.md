# Plan de mejora de carga y experiencia de usuario

Fecha: 2026-07-22

## Diagnóstico

La subida de archivos ejecutaba dentro de la petición HTTP la copia, comprobación de espacio, hash SHA-256, inspección con `ffprobe`, validación de códec, miniatura con FFmpeg y un escaneo de almacenamiento. En archivos grandes el navegador permanecía esperando hasta que terminaba toda la cadena. El editor tampoco mostraba porcentaje transferido y aceptaba formatos que el backend rechazaba después.

El reproductor obtenía cada imagen y vídeo mediante PHP. Aunque las respuestas permitían caché y rangos, cada transferencia larga podía mantener ocupado un proceso PHP-FPM.

## Decisión de arquitectura

Se utiliza la cola `database` que ya forma parte de Laravel y un único contenedor `worker`. Para una instalación con SQLite, una pantalla y un único administrador, Redis añadiría consumo de memoria, operación y un punto de fallo sin reducir el tiempo de transferencia por red. Redis se reconsiderará si aparecen varios workers, varias pantallas o una tasa sostenida de cientos de trabajos.

El nuevo flujo es:

1. Comprobar espacio disponible antes de transferir.
2. Guardar el original y crear un registro con estado `processing`.
3. Responder con HTTP 202 y liberar la interfaz.
4. Calcular hash, validar y generar miniatura en el worker.
5. Informar al editor por sondeo corto y añadir el contenido a la zona al quedar listo.
6. Reutilizar el contenido existente cuando el hash revela un duplicado.

## Cambios aplicados

- Worker separado para la cola `media`, con reinicio y cierre controlados por Docker.
- Estados visibles `Procesando`, `Listo`, `Duplicado reutilizado` y `Revisar`.
- Progreso porcentual real durante la transferencia del editor visual.
- Sondeo de trabajos pendientes sin bloquear el guardado del diseño.
- Validación en segundo plano con mensajes de error seguros para el usuario.
- Eliminación del escaneo completo de disco tras cada archivo; las métricas conservan su refresco periódico.
- SQLite en modo WAL, espera de bloqueo y sincronización normal para reducir contención entre web, scheduler y worker.
- Caché de archivo para evitar escrituras de caché innecesarias en SQLite.
- Entrega de medios y miniaturas por Nginx mediante `X-Accel-Redirect` en producción.
- Restricción coherente de formatos visibles a JPEG, PNG, WebP y MP4/H.264.

## Mejoras posteriores priorizadas

1. Subida reanudable por fragmentos si la LAN o los archivos de varios GB producen cortes reales.
2. Endpoint paginado de biblioteca para sustituir el límite actual de 500 contenidos.
3. Indicadores de longitud y antigüedad de cola en el dashboard.
4. Redis solo al superar el perfil de una instalación/pantalla o si las métricas demuestran contención persistente.
5. Conversión opcional de vídeos no H.264, únicamente si el hardware y el flujo de negocio lo justifican.
