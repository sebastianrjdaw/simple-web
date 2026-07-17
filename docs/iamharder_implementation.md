# Simple View — Bloques AIMHARDER mediante iframe

Utiliza como contexto:

* `SIMPLE_VIEW_CODEX_CONTEXT.md`
* `SIMPLE_VIEW_PHASE_2.md`
* la ampliación de eliminación, backups, filtros y rendimiento;
* el código actual de la versión estable.

Implementa un nuevo tipo de contenido para mostrar el WOD público de AIMHARDER dentro de las zonas del editor visual.

Ejemplo que debe soportarse:

```html
<iframe src="https://gamancrossfit.aimharder.com/navwod"></iframe>
```

La interfaz no debe pedir al administrador que pegue código HTML. El usuario solo debe introducir o configurar la URL:

```text
https://gamancrossfit.aimharder.com/navwod
```

Simple View será responsable de generar el iframe de forma segura.

## Objetivo funcional

Permitir que una zona de una plantilla muestre una página pública de AIMHARDER, especialmente la vista `navwod`, junto con las zonas habituales de imágenes y vídeos.

Ejemplo de uso:

```text
┌──────────────────────────────┬───────────────┐
│                              │               │
│    Fotografías y vídeos      │ WOD del día   │
│                              │ AIMHARDER     │
│                              │               │
└──────────────────────────────┴───────────────┘
```

El bloque AIMHARDER debe integrarse en el editor visual principal y no en el editor clásico, que está siendo retirado.

## 1. Nuevo tipo de contenido

Añadir un tipo de contenido con un nombre interno equivalente a:

```text
web_embed
```

Y nombre visible:

```text
AIMHARDER / Página web
```

No almacenar HTML arbitrario.

Guardar únicamente datos estructurados como:

* nombre;
* proveedor;
* URL;
* tipo de contenido;
* intervalo de actualización;
* interacción activada o desactivada;
* imagen de respaldo;
* fecha de creación;
* fecha de modificación;
* estado de validación.

Ejemplo conceptual:

```json
{
  "type": "web_embed",
  "provider": "aimharder",
  "name": "WOD del día",
  "url": "https://gamancrossfit.aimharder.com/navwod",
  "refresh_interval_minutes": 15,
  "interaction_enabled": false,
  "fallback_media_id": null
}
```

Adapta los nombres al modelo real del proyecto.

## 2. Seguridad de URL

No permitir que el navegador reciba HTML proporcionado por el administrador.

Validar en servidor:

* protocolo obligatorio `https`;
* URL sintácticamente válida;
* ausencia de credenciales en la URL;
* ausencia de esquemas como `javascript:`, `data:` o `file:`;
* host permitido;
* longitud máxima razonable;
* normalización antes de guardar.

Para la primera versión, utilizar una lista blanca.

Hosts permitidos inicialmente:

```text
aimharder.com
*.aimharder.com
```

Permitir configurar técnicamente esta lista mediante configuración del proyecto, pero no mostrar un editor libre de dominios al cliente.

Evitar que el sistema pueda utilizarse para acceder mediante iframe a:

* localhost;
* direcciones privadas;
* paneles del router;
* servicios internos;
* archivos locales;
* otros hosts no autorizados.

No implementar un proxy del contenido de AIMHARDER.

No intentar eliminar ni sortear las políticas de seguridad que envíe el servidor remoto.

## 3. Integración en el editor visual

En el selector de contenidos de una zona, añadir:

```text
[ Imagen ]
[ Vídeo ]
[ WOD de AIMHARDER ]
```

También puede mostrarse como:

```text
[ Añadir página web ]
```

Pero AIMHARDER debe aparecer como opción destacada o preconfigurada.

Al pulsar `WOD de AIMHARDER`, abrir un formulario sencillo:

* Nombre visible.
* URL de AIMHARDER.
* Actualización automática.
* Interacción.
* Imagen de respaldo.
* Botón `Probar visualización`.
* Botón `Añadir a esta zona`.

Valor inicial sugerido:

```text
Nombre: WOD del día
URL: https://gamancrossfit.aimharder.com/navwod
Actualización: cada 15 minutos
Interacción: desactivada
```

La acción debe añadir el bloque directamente a la zona desde la que fue abierto el formulario.

## 4. Representación en el lienzo

Dentro del editor visual no cargar automáticamente la página completa de AIMHARDER en todas las zonas.

Mostrar una tarjeta representativa:

```text
┌─────────────────────────────┐
│ AIMHARDER                   │
│                             │
│ WOD del día                 │
│ gamancrossfit.aimharder.com │
│                             │
│ [Vista previa]              │
└─────────────────────────────┘
```

La tarjeta debe incluir:

* icono de página web;
* proveedor;
* nombre;
* dominio;
* estado;
* botón de vista previa;
* botón de configuración.

Esto evita cargar iframes externos continuamente dentro del editor.

El iframe real debe cargarse en:

* la vista previa;
* la ruta de reproducción;
* una prueba explícita iniciada por el administrador.

## 5. Regla de una zona con iframe

Para la primera versión, un bloque AIMHARDER debe ocupar de forma exclusiva una zona.

No permitir mezclar dentro de la misma zona:

* un iframe;
* fotografías;
* vídeos;
* otros iframes.

Cuando una zona ya contenga imágenes o vídeos y el usuario intente añadir AIMHARDER, mostrar:

```text
Esta zona ya contiene una lista de imágenes o vídeos.

Para mostrar AIMHARDER, vacía la zona o selecciona otra zona.
```

Cuando una zona ya tenga AIMHARDER, no permitir añadir otro contenido sin sustituirlo explícitamente.

Esta restricción simplifica:

* reproducción;
* actualizaciones;
* memoria;
* estados;
* vista previa;
* gestión de errores.

No elimina la posibilidad de utilizar otras zonas para vídeos e imágenes.

## 6. Renderizado del iframe

En reproducción, generar un iframe equivalente a:

```html
<iframe
    src="https://gamancrossfit.aimharder.com/navwod"
    title="WOD del día"
    loading="eager"
    referrerpolicy="strict-origin-when-cross-origin"
    sandbox="allow-scripts allow-same-origin allow-forms"
></iframe>
```

El elemento debe ocupar completamente la zona:

```css
width: 100%;
height: 100%;
border: 0;
display: block;
background: #000;
```

Revisar durante la prueba real si AIMHARDER necesita permisos adicionales de `sandbox`.

No añadir permisos innecesarios como:

* acceso a cámara;
* acceso a micrófono;
* descargas;
* apertura libre de ventanas;
* navegación de la ventana superior.

Si AIMHARDER no funciona con el `sandbox` inicial, documentar qué permiso concreto necesita antes de ampliarlo.

## 7. Interacción

Añadir una opción:

```text
Permitir interacción con la página
```

Valor predeterminado:

```text
Desactivada
```

Cuando esté desactivada:

* mostrar el iframe normalmente;
* colocar una capa transparente sobre él;
* usar `pointer-events: none`;
* impedir clics accidentales;
* permitir que la pantalla funcione como cartelería pasiva.

Cuando esté activada:

* permitir clics y desplazamiento;
* mostrar una advertencia indicando que la página podrá recibir interacción;
* mantener bloqueada cualquier navegación fuera del iframe.

En el escenario actual del box de CrossFit, utilizar por defecto el modo no interactivo.

## 8. Ajuste y desplazamiento

El iframe debe ocupar todo el tamaño disponible, pero no se puede modificar libremente el diseño interno de una página externa.

Añadir opciones sencillas:

* Mostrar página completa.
* Permitir desplazamiento.
* Ocultar desplazamiento.

Valor predeterminado:

```text
Mostrar página completa
Desplazamiento automático del iframe desactivado
```

No intentar aplicar estilos CSS dentro del documento remoto, porque pertenece a otro origen.

Si la página de AIMHARDER no se adapta correctamente a una zona estrecha, mostrar una recomendación:

```text
Este contenido funciona mejor en una zona vertical amplia o a pantalla completa.
```

## 9. Actualización automática

Permitir configurar la recarga del iframe.

Opciones:

* Sin recarga periódica.
* Cada 5 minutos.
* Cada 15 minutos.
* Cada 30 minutos.
* Cada 60 minutos.

Valor predeterminado:

```text
Cada 15 minutos
```

La actualización debe:

* recargar solo el iframe;
* no recargar la página completa del reproductor;
* no reiniciar las listas de imágenes o vídeos de otras zonas;
* pausar el temporizador cuando el documento no esté visible, cuando resulte apropiado;
* reiniciarse de manera segura después de una nueva publicación.

Evitar añadir continuamente parámetros a la URL que puedan producir crecimiento de caché o comportamientos inesperados.

## 10. Dependencia de Internet

Los contenidos de AIMHARDER dependen de Internet, a diferencia de las imágenes y vídeos locales.

Mostrar claramente en el editor:

```text
Este contenido necesita conexión a Internet.
```

Añadir un indicador visual:

* Disponible.
* Pendiente de comprobación.
* No disponible.
* Bloqueado por el proveedor.
* Sin conexión.

No prometer una validación perfecta desde el backend, porque una respuesta HTTP correcta no garantiza que el navegador permita cargar el iframe.

## 11. Imagen de respaldo

Permitir asignar una imagen local como respaldo.

Cuando el iframe no pueda mostrarse o no exista conexión, utilizar:

* la imagen de respaldo específica del bloque;
* en su defecto, el logotipo global;
* en último término, fondo negro.

Mostrar un mensaje opcional y discreto:

```text
El WOD no está disponible temporalmente.
```

No mostrar errores técnicos, encabezados HTTP ni excepciones en la pantalla pública.

## 12. Detección de carga y errores

Por tratarse de contenido de otro origen, el reproductor no podrá inspeccionar libremente el documento interno del iframe.

Implementar una detección razonable:

1. Mostrar estado `Cargando`.
2. Escuchar el evento `load`.
3. Aplicar un tiempo máximo de espera.
4. Mostrar respaldo si no se produce carga.
5. Reintentar con backoff.
6. Registrar el error.
7. Notificarlo en el panel.

El evento `load` no garantiza que el contenido remoto sea visualmente correcto. Documentar esta limitación.

No realizar reintentos constantes.

Propuesta:

* primer reintento: 30 segundos;
* segundo reintento: 2 minutos;
* siguientes: cada 5 minutos;
* restaurar frecuencia normal cuando vuelva a cargar.

## 13. Prueba desde el panel

Añadir una acción:

```text
Probar visualización
```

Debe abrir una ventana o diálogo de vista previa con la misma política utilizada por el reproductor.

La prueba debe indicar:

* si la URL es válida;
* si el dominio está permitido;
* si existe conectividad;
* si se ha recibido una respuesta;
* si el iframe ha disparado el evento de carga;
* si se ha detectado un bloqueo conocido.

Si el navegador informa de un bloqueo por `X-Frame-Options` o CSP, mostrar:

```text
AIMHARDER no permite mostrar esta dirección dentro de Simple View.

Consulta con AIMHARDER si existe una URL pública preparada para incrustar.
```

No intentar eludir el bloqueo mediante proxies, capturas automatizadas o eliminación de encabezados.

## 14. Política de seguridad de contenidos de Simple View

Actualizar la CSP de Simple View para permitir exclusivamente los marcos necesarios.

Añadir una directiva equivalente a:

```text
frame-src 'self' https://aimharder.com https://*.aimharder.com;
```

Adaptarla a la configuración real del proxy o servidor web.

No utilizar:

```text
frame-src *
```

No relajar otras directivas sin necesidad.

Comprobar que la política de `frame-ancestors` de Simple View no se confunda con `frame-src`.

## 15. Gestión desde la biblioteca

Los bloques AIMHARDER deben aparecer en la biblioteca o selector con una categoría:

```text
Páginas web
```

Filtros:

* Todos.
* Imágenes.
* Vídeos.
* Páginas web.
* AIMHARDER.

Mostrar:

* nombre;
* proveedor;
* dominio;
* fecha;
* zonas donde se utiliza;
* estado;
* necesidad de Internet.

No mostrar una miniatura falsa que pueda confundirse con una captura actual.

## 16. Eliminación

Aplicar las reglas generales de eliminación definidas para contenidos.

Un bloque AIMHARDER:

* no tiene un archivo multimedia físico;
* ocupa muy poco almacenamiento;
* puede estar usado por un diseño;
* puede estar usado por la publicación activa.

Si está en la publicación activa:

* bloquear su eliminación;
* pedir que se retire del diseño;
* requerir una nueva publicación.

Si solo aparece en diseños inactivos:

* permitir retirarlo de esos diseños y eliminarlo;
* solicitar confirmación.

No indicar que eliminarlo liberará espacio multimedia significativo.

## 17. Copias de seguridad

Incluir en las copias:

* URL;
* nombre;
* proveedor;
* configuración;
* intervalo de actualización;
* interacción;
* referencia a imagen de respaldo;
* asociaciones con zonas.

No intentar copiar el contenido remoto de AIMHARDER.

Al restaurar una copia, validar nuevamente:

* dominio;
* protocolo;
* configuración de seguridad.

## 18. Reproducción fuera del horario

Fuera del horario comercial:

* no cargar el iframe;
* detener sus recargas;
* mostrar el contenido global configurado para fuera de horario.

Al comenzar el horario:

* cargar el iframe;
* iniciar su ciclo de actualización;
* mantener independientes las demás zonas.

Esto evita tráfico y consumo innecesario cuando el establecimiento está cerrado.

## 19. Rendimiento

Aplicar estas reglas:

* no cargar el iframe dentro de las tarjetas de biblioteca;
* no cargarlo continuamente en el editor;
* cargarlo solo en preview o display;
* destruirlo al cambiar de diseño;
* cancelar temporizadores al desmontar el componente;
* no mantener iframes invisibles;
* no crear múltiples intervalos de actualización;
* no usar capturas automáticas frecuentes;
* no realizar comprobaciones del servidor remoto en cada render.

Medir el impacto cuando existan varios bloques web en distintas zonas.

Para el MVP, establecer un máximo configurable de cuatro iframes simultáneos por pantalla, aunque normalmente se utilizará uno.

## 20. Modelo de datos y compatibilidad

Antes de crear tablas nuevas, revisar si el modelo actual de contenidos admite tipos polimórficos o metadatos.

Preferir una extensión limpia del modelo existente.

Posibles enfoques:

* añadir tipo `web_embed` a la tabla de contenidos;
* crear una tabla específica relacionada;
* utilizar un campo JSON validado para opciones no comunes.

No guardar HTML completo.

No utilizar serialización insegura.

Crear las migraciones necesarias sin alterar los recursos existentes.

## 21. Pruebas

Añadir pruebas para:

* crear un bloque AIMHARDER válido;
* rechazar HTTP;
* rechazar `javascript:`;
* rechazar hosts no autorizados;
* aceptar subdominios de AIMHARDER;
* añadir el bloque a una zona vacía;
* impedir añadirlo a una zona multimedia ocupada;
* mostrarlo en vista previa;
* mostrarlo en reproducción;
* aplicar CSP correcta;
* recargar solo el iframe;
* detener recarga fuera de horario;
* activar y desactivar interacción;
* usar respaldo sin conexión;
* bloquear eliminación cuando está publicado;
* eliminarlo cuando no está utilizado;
* incluirlo en backups;
* restaurarlo;
* filtrar por páginas web;
* evitar HTML arbitrario;
* evitar URLs internas;
* limpiar temporizadores al cambiar de diseño.

Añadir pruebas de navegador cuando sean necesarias para iframe, carga, interacción y vista previa.

## 22. Criterios de aceptación

La funcionalidad se considerará completada cuando:

1. El administrador pueda añadir un WOD de AIMHARDER sin escribir HTML.
2. La URL se valide en servidor.
3. Solo se admitan dominios autorizados.
4. El bloque pueda asignarse visualmente a una zona.
5. El editor muestre una tarjeta ligera, no el iframe completo.
6. La vista previa cargue el iframe real.
7. La pantalla pública cargue el iframe real.
8. El iframe ocupe toda la zona.
9. El contenido permanezca silenciado y sin permisos innecesarios.
10. La interacción esté desactivada de forma predeterminada.
11. Se pueda configurar una actualización periódica.
12. Las demás zonas no se reinicien al actualizar AIMHARDER.
13. Fuera del horario no se cargue el iframe.
14. Exista una imagen de respaldo.
15. El panel indique que necesita Internet.
16. Se detecten y comuniquen razonablemente los errores.
17. La política CSP no permita dominios generales.
18. La publicación activa nunca quede con una referencia eliminada.
19. El bloque se incluya en backups.
20. No se permita pegar HTML arbitrario.
21. No se intente eludir un bloqueo impuesto por AIMHARDER.
22. Las pruebas existentes sigan superándose.

## 23. Documentación

Actualizar:

* contexto funcional;
* documentación de fase 2;
* manual de usuario;
* configuración CSP;
* variables de entorno;
* backups;
* resolución de problemas.

Añadir una sección:

```text
Integración con AIMHARDER
```

Debe explicar:

1. Cómo obtener la dirección pública del WOD.
2. Cómo añadirla.
3. Cómo probarla.
4. Que requiere Internet.
5. Cómo configurar respaldo.
6. Qué hacer si AIMHARDER bloquea el iframe.
7. Cómo retirar el bloque de un diseño.
8. Cómo eliminarlo.

## 24. Ejecución

Trabaja de manera incremental:

1. Revisa el modelo actual.
2. Añade validación y modelo.
3. Añade formulario.
4. Integra el editor visual.
5. Integra vista previa.
6. Integra reproductor.
7. Añade respaldo y errores.
8. Añade CSP.
9. Integra eliminación, filtros y backups.
10. Ejecuta pruebas.
11. Verifica manualmente la URL real.
12. Documenta resultados.

No marques la tarea como completada hasta probar en un navegador real:

```text
https://gamancrossfit.aimharder.com/navwod
```

Si la URL no permite incrustación, conserva la implementación general, documenta el bloqueo y solicita al proveedor una URL pública compatible con iframe.
