# Simple View — Ampliación de Fase 2: eliminación segura, editor visual principal, backups, filtros y optimización

Utiliza como fuentes principales:

* `SIMPLE_VIEW_CODEX_CONTEXT.md`
* `SIMPLE_VIEW_PHASE_2.md`
* el código actual del repositorio;
* las migraciones y pruebas existentes.

La aplicación dispone de una versión estable. Esta tarea debe implementarse de manera incremental, evitando reescrituras generales, pérdida de datos o regresiones en la reproducción.

Antes de modificar código:

1. Inspecciona la implementación actual.
2. Identifica modelos, servicios, páginas, rutas y componentes relacionados con:

   * contenidos multimedia;
   * biblioteca y selector de contenidos;
   * diseños;
   * plantillas;
   * borradores;
   * publicación activa;
   * editor clásico;
   * editor visual;
   * almacenamiento;
   * backups;
   * listados administrativos.
3. Ejecuta la suite de pruebas actual.
4. Documenta brevemente cualquier diferencia entre el modelo descrito en los archivos Markdown y el modelo realmente implementado.
5. Presenta un plan de cambios incremental.
6. Implementa el plan sin detenerte por diferencias menores de nomenclatura.
7. Reutiliza los servicios de dominio existentes y evita duplicar lógica.

## Objetivos

Esta ampliación debe conseguir:

1. Poder eliminar contenidos desde el selector multimedia del editor visual.
2. Poder eliminar contenidos desde la página de gestión de almacenamiento.
3. Impedir la eliminación accidental de recursos que se están reproduciendo.
4. Permitir eliminar diseños que no estén activos ni reproduciéndose.
5. Retirar el editor clásico y establecer el editor visual como única experiencia principal.
6. Añadir una configuración intuitiva de copias de seguridad.
7. Garantizar como mínimo una copia de seguridad cada dos días.
8. Añadir filtros útiles en todos los listados.
9. Revisar y mejorar el rendimiento general.
10. Revisar y mejorar la experiencia de usuario.
11. Mantener la versión estable y los datos existentes.

---

# 1. Editor visual como editor principal

El editor visual definido en `SIMPLE_VIEW_PHASE_2.md` pasa a ser el único editor funcional mostrado al administrador.

El editor clásico debe dejar de aparecer en:

* navegación principal;
* dashboard;
* acciones rápidas;
* enlaces de edición;
* botones de diseños;
* rutas generadas por el panel;
* enlaces desde notificaciones o listados.

Todas las acciones de edición deben abrir el editor visual.

## 1.1 Compatibilidad de enlaces existentes

Si existen rutas antiguas guardadas en favoritos o enlazadas desde otros componentes:

* redirigirlas al editor visual equivalente;
* conservar el identificador del diseño cuando sea posible;
* mostrar un aviso no intrusivo indicando que se ha abierto el nuevo editor;
* no devolver errores innecesarios al usuario.

Ejemplo:

```text
/editor-clasico/15
```

Debe redirigir a algo equivalente a:

```text
/editor-visual/15
```

## 1.2 Retirada técnica

No eliminar inicialmente servicios de dominio que también utilicen:

* publicación;
* vista previa;
* reproducción;
* validación;
* gestión de zonas;
* listas de reproducción.

Eliminar únicamente:

* páginas clásicas sin uso;
* componentes visuales obsoletos;
* rutas duplicadas;
* controladores exclusivos del editor clásico;
* estilos y JavaScript que hayan quedado sin referencias.

Antes de borrar código:

1. Buscar todas sus referencias.
2. Confirmar que el editor visual cubre la misma operación.
3. Añadir o actualizar las pruebas.
4. Eliminar el código muerto.
5. Ejecutar análisis estático y suite de pruebas.

No conservar dos editores funcionales en paralelo.

## 1.3 Navegación principal

La navegación debe ser sencilla:

* Inicio.
* Diseños.
* Editor visual.
* Contenidos.
* Almacenamiento.
* Copias de seguridad.
* Configuración.

Evitar mostrar al cliente apartados técnicos innecesarios.

---

# 2. Eliminación de contenidos multimedia

La eliminación debe estar disponible desde:

1. El selector o biblioteca de contenidos integrado en el editor visual.
2. El listado general de contenidos.
3. La página de gestión de almacenamiento.
4. La lista de archivos grandes.
5. La sección de contenidos no utilizados.

Toda eliminación debe pasar por un único servicio de dominio, por ejemplo:

```text
MediaDeletionService
```

No implementar reglas distintas en cada página.

## 2.1 Acciones visibles

Cada tarjeta o fila de contenido debe disponer de un menú contextual con:

* Ver detalles.
* Descargar original.
* Ver dónde se utiliza.
* Eliminar.

En la página de almacenamiento también debe existir:

* selección múltiple;
* acción `Eliminar seleccionados`;
* suma del espacio que se liberará;
* confirmación antes de ejecutar.

En dispositivos con ratón, las acciones no deben depender exclusivamente de estados `hover`. Deben ser accesibles mediante un botón visible o menú de tres puntos.

## 2.2 Cálculo de usos

Antes de permitir una eliminación, comprobar todas las referencias relevantes:

* publicación activa;
* diseño activo;
* borrador actual;
* diseños guardados no activos;
* zonas;
* listas de reproducción;
* imagen de respaldo;
* logotipo;
* contenidos configurados fuera del horario;
* otras referencias reales encontradas en el código.

No asumir que `usage_count` está actualizado sin verificarlo.

Mostrar un resumen comprensible:

```text
Este vídeo se utiliza en:

• Diseño “Pantalla principal”
• Zona “Lateral derecho”
• Publicación activa
```

Añadir una acción `Ver usos` que permita abrir los diseños afectados cuando sea posible.

## 2.3 Recurso utilizado por la publicación activa

Si una imagen o vídeo está referenciado por la publicación que se reproduce actualmente:

* bloquear su eliminación;
* no mostrar una confirmación que permita forzarla;
* explicar claramente el motivo;
* ofrecer una acción para abrir el diseño activo;
* indicar que debe retirarse y publicarse otro contenido antes de eliminarlo.

Mensaje recomendado:

```text
No se puede eliminar este contenido porque se está mostrando actualmente.

Retíralo del diseño, publica los cambios y vuelve a intentar la eliminación.
```

La publicación activa nunca debe quedar apuntando a un archivo inexistente.

## 2.4 Recurso usado como respaldo

No permitir eliminar directamente:

* logotipo activo;
* imagen de respaldo;
* imagen mostrada fuera del horario.

Debe sustituirse primero por otro recurso desde Configuración.

La interfaz debe ofrecer la acción:

```text
Sustituir imagen de respaldo
```

## 2.5 Recurso no utilizado

Cuando el recurso no tenga referencias:

1. Mostrar nombre, miniatura y tamaño.
2. Mostrar el espacio que se liberará.
3. Solicitar confirmación.
4. Eliminar miniaturas y archivos derivados.
5. Eliminar el archivo original.
6. Eliminar o marcar el registro correspondiente.
7. Actualizar las métricas de almacenamiento.
8. Registrar la acción.
9. Mostrar el resultado.

Ejemplo:

```text
Se ha eliminado “Promoción de verano.mp4”.
Espacio liberado: 1,4 GB.
```

## 2.6 Recurso usado solo en diseños no activos

Cuando un recurso no esté en la publicación activa, pero aparezca en borradores o diseños inactivos, mostrar dos opciones:

### Cancelar

No modificar nada.

### Retirar de diseños inactivos y eliminar

Esta opción debe:

1. Enumerar los diseños afectados.
2. Explicar que será retirado de sus zonas.
3. Solicitar confirmación reforzada.
4. Retirar sus asociaciones dentro de una transacción.
5. Mantener el resto del orden de las listas.
6. Dejar la zona vacía si no contiene otros elementos.
7. Eliminar el recurso físico.
8. Actualizar miniaturas, métricas y contadores.
9. Registrar todos los diseños modificados.

No modificar la publicación activa.

Mensaje recomendado:

```text
Este contenido se utiliza en 3 diseños que no están activos.

Al continuar, se retirará de esos diseños y se eliminará definitivamente.
```

## 2.7 Eliminación múltiple

La eliminación múltiple debe clasificar los recursos antes de ejecutar:

* eliminables;
* usados solo en diseños no activos;
* bloqueados por publicación activa;
* bloqueados por configuración;
* archivos con errores.

Mostrar un resumen:

```text
12 archivos seleccionados

8 se pueden eliminar
2 requieren retirarse de diseños no activos
2 no pueden eliminarse porque están en reproducción
```

No abortar toda la operación porque uno de los archivos esté bloqueado.

Procesar los elementos permitidos y devolver un resultado individual por archivo.

## 2.8 Consistencia y errores

La operación debe protegerse frente a:

* fallo al eliminar el archivo;
* archivo físico inexistente;
* error de base de datos;
* interrupción del proceso;
* referencias creadas durante la operación.

Utilizar transacciones, bloqueos o estados intermedios cuando sea necesario.

No eliminar primero el registro y dejar un archivo sin controlar.

Registrar:

* usuario;
* recurso;
* tamaño;
* referencias retiradas;
* fecha;
* resultado;
* error, cuando exista.

Después de eliminar:

* invalidar cachés;
* actualizar métricas;
* actualizar el selector de contenidos;
* actualizar el panel de almacenamiento;
* regenerar contadores;
* no recargar toda la página cuando no sea necesario.

---

# 3. Eliminación de diseños

Añadir una opción para eliminar diseños desde:

* listado de diseños;
* menú contextual del editor visual;
* página de detalles del diseño.

La eliminación de un diseño no debe eliminar sus imágenes o vídeos de la biblioteca.

## 3.1 Diferenciar plantilla y diseño

Antes de implementar, identifica claramente la nomenclatura real del proyecto.

A efectos de estos requisitos:

* **Plantilla:** estructura de zonas, como pantalla completa, dos columnas o cuadrícula.
* **Diseño:** composición creada por el usuario que utiliza una plantilla y contiene recursos.

Las plantillas internas del sistema no deben eliminarse desde el panel.

Los diseños creados por el usuario sí pueden eliminarse aplicando las reglas siguientes.

## 3.2 Diseño activo

No permitir eliminar:

* el diseño que se está reproduciendo;
* la publicación activa;
* un diseño asignado como respaldo;
* un diseño programado, si en el futuro existe programación;
* el único diseño necesario para mantener una publicación válida.

Mensaje recomendado:

```text
No se puede eliminar este diseño porque se está reproduciendo actualmente.

Publica otro diseño y vuelve a intentarlo.
```

Ofrecer:

* `Abrir diseño`;
* `Duplicar diseño`;
* `Publicar otro diseño`.

## 3.3 Diseño no activo

Permitir eliminar un diseño cuando:

* no sea la publicación activa;
* no esté asignado como respaldo;
* no esté en reproducción;
* no tenga una operación de publicación pendiente;
* no esté abierto por otra operación que pueda provocar inconsistencias.

La confirmación debe mostrar:

* nombre;
* plantilla;
* última modificación;
* número de zonas;
* número de contenidos asociados.

Indicar claramente:

```text
Los archivos multimedia no se eliminarán de la biblioteca.
```

## 3.4 Borrador abierto

Si el diseño que se intenta eliminar es el que está abierto en el editor:

* solicitar confirmación;
* cerrar el editor de ese diseño;
* dirigir al listado de diseños después de eliminar;
* no dejar el navegador apuntando a un registro inexistente.

## 3.5 Último diseño

La aplicación debe conservar como mínimo una situación válida.

Si solo existe un diseño:

* bloquear su eliminación; o
* crear primero un diseño vacío predeterminado.

Priorizar la opción más sencilla y segura según la arquitectura existente.

## 3.6 Eliminación múltiple de diseños

Añadir selección múltiple en el listado.

Antes de eliminar, clasificar:

* diseños eliminables;
* diseño activo;
* diseños bloqueados;
* diseños con errores.

Eliminar únicamente los permitidos y mostrar un resumen.

## 3.7 Registro

Registrar:

* usuario;
* diseño eliminado;
* fecha;
* número de asociaciones retiradas;
* motivo del bloqueo cuando no pueda eliminarse.

---

# 4. Gestión de almacenamiento integrada con la eliminación

La página `Gestionar almacenamiento` debe convertirse en un espacio práctico para liberar capacidad, no solo en una pantalla informativa.

Debe incluir:

* porcentaje de disco utilizado;
* espacio libre real;
* reserva protegida;
* espacio ocupado por Simple View;
* archivos grandes;
* contenidos no utilizados;
* diseños no activos;
* copias antiguas;
* temporales;
* logs;
* espacio recuperable;
* recomendaciones.

## 4.1 Contenidos no utilizados

Mostrar una tabla o cuadrícula con:

* miniatura;
* nombre;
* tipo;
* tamaño;
* fecha de subida;
* última utilización;
* número de usos;
* estado;
* selección;
* acción de eliminar.

Filtros mínimos:

* imágenes;
* vídeos;
* tamaño;
* fecha;
* nunca utilizados;
* sin uso actual;
* nombre.

Ordenaciones mínimas:

* mayor tamaño;
* más antiguo;
* más reciente;
* nombre;
* última utilización.

## 4.2 Archivos grandes

Permitir:

* ver detalles;
* ver usos;
* descargar;
* eliminar cuando sea seguro;
* selección múltiple.

Mostrar cuánto espacio se liberaría antes de confirmar.

## 4.3 Diseños no activos

Mostrar diseños que no estén reproduciéndose con:

* nombre;
* plantilla;
* última modificación;
* contenidos;
* tamaño aproximado de los recursos asociados;
* acción de abrir;
* acción de eliminar.

No presentar el tamaño de recursos asociados como espacio que se liberará al eliminar el diseño, porque eliminar el diseño no elimina los archivos multimedia.

## 4.4 Recomendaciones accionables

Las recomendaciones deben incluir botones directos.

Ejemplos:

```text
Hay 8 vídeos no utilizados que ocupan 12,6 GB.
[Revisar vídeos]
```

```text
Hay 4 copias antiguas que ocupan 5,2 GB.
[Gestionar copias]
```

```text
Hay 17 archivos temporales que pueden eliminarse.
[Limpiar temporales]
```

Diferenciar las acciones que puede ejecutar el administrador de las reservadas al técnico.

---

# 5. Configuración intuitiva de copias de seguridad

Añadir una sección clara en Configuración llamada:

```text
Copias de seguridad
```

No obligar al usuario a editar variables `.env` para configurar la programación habitual.

## 5.1 Principio de seguridad

Simple View debe realizar como mínimo una copia válida cada dos días.

La aplicación no debe permitir guardar una programación cuyo intervalo máximo pueda superar 48 horas.

Configuraciones admitidas inicialmente:

* una vez al día;
* cada dos días.

Se puede permitir más de una copia diaria si la arquitectura ya lo soporta.

No ofrecer una frecuencia semanal porque incumpliría el mínimo requerido.

## 5.2 Formulario

La configuración debe incluir:

### Activación

```text
Copias automáticas: Activadas
```

En producción, no permitir desactivar permanentemente las copias sin:

* confirmación reforzada;
* advertencia visible;
* registro técnico;
* indicación permanente en el dashboard.

Preferiblemente, mantener las copias automáticas obligatorias y permitir únicamente modificar su frecuencia.

### Frecuencia

Opciones visuales:

* Todos los días.
* Cada dos días.

Valor predeterminado:

```text
Cada dos días
```

### Hora

Selector horario sencillo.

Valor recomendado:

```text
03:00
```

### Tipo de copia

Cuando el sistema actual lo permita:

* configuración y base de datos;
* copia completa con multimedia.

Explicar la diferencia y el espacio estimado.

### Destino

Mostrar destinos disponibles según la instalación:

* almacenamiento local;
* unidad USB;
* carpeta de red;
* destino configurado por el técnico.

No mostrar destinos que no estén configurados.

Añadir acción:

```text
Comprobar destino
```

La prueba debe verificar:

* accesibilidad;
* permisos de escritura;
* espacio disponible;
* posibilidad de crear y eliminar un archivo temporal.

### Retención

Permitir configurar cuántas copias se conservan.

Valores razonables:

* mínimo: 3;
* predeterminado: 7;
* máximo configurable según espacio disponible.

Mostrar una estimación:

```text
Espacio estimado para 7 copias: 18,4 GB
```

### Información de estado

Mostrar:

* última copia correcta;
* última copia fallida;
* próxima copia prevista;
* duración de la última copia;
* tamaño;
* destino;
* número de copias conservadas;
* estado del destino.

### Acciones

Incluir:

* Crear copia ahora.
* Descargar última copia.
* Ver historial.
* Comprobar destino.
* Restaurar, solo cuando el flujo seguro ya exista.

## 5.3 Validación de intervalo

La validación debe ejecutarse:

* en el navegador;
* en el servidor;
* al cargar la configuración;
* antes de guardar.

No confiar únicamente en la interfaz.

Si por una migración o cambio manual aparece una frecuencia superior a 48 horas:

* marcar la configuración como no válida;
* mostrar una advertencia;
* usar temporalmente el valor seguro predeterminado;
* solicitar corrección.

## 5.4 Recuperación tras apagados

Si el OptiPlex estaba apagado en el momento programado:

* detectar la copia perdida al arrancar;
* ejecutar una copia de recuperación cuando sea seguro;
* evitar lanzar varias copias simultáneas;
* respetar la reserva de almacenamiento;
* registrar que se trata de una ejecución recuperada.

Si han pasado más de 48 horas desde la última copia válida, mostrar estado crítico en el dashboard:

```text
La última copia de seguridad válida tiene más de dos días.
```

## 5.5 Integración con almacenamiento

Antes de una copia:

1. Estimar tamaño.
2. Consultar métricas recientes.
3. Aplicar retención de manera segura.
4. Comprobar destino.
5. Verificar que se respeta la reserva.
6. Crear la copia temporal.
7. Validarla.
8. Marcarla como válida.
9. Aplicar la retención restante.

Nunca eliminar la última copia válida antes de confirmar la nueva.

---

# 6. Filtros en todos los listados

Todos los listados administrativos deben disponer de filtros basados en campos relevantes.

Los filtros deben ejecutarse en el servidor cuando el volumen pueda ser elevado.

No cargar todos los registros para filtrarlos únicamente en el navegador.

## 6.1 Comportamiento común

Todos los listados deben incluir, cuando sea aplicable:

* buscador;
* filtros desplegables;
* ordenación por columnas relevantes;
* botón `Limpiar filtros`;
* contador de resultados;
* paginación;
* estado vacío específico;
* persistencia de filtros en la URL o sesión;
* indicación visible de filtros activos.

Los filtros deben combinarse entre sí.

No ocultar resultados sin indicar que hay filtros activos.

## 6.2 Contenidos

Filtros recomendados:

* nombre;
* tipo: imagen o vídeo;
* estado;
* utilizado o no utilizado;
* utilizado en publicación activa;
* tamaño;
* rango de fechas;
* fecha de última utilización;
* compatibilidad;
* archivos con error.

Ordenaciones:

* nombre;
* tamaño;
* fecha de subida;
* última utilización;
* número de usos.

## 6.3 Diseños

Filtros recomendados:

* nombre;
* plantilla;
* activo o inactivo;
* publicado o borrador;
* número de zonas;
* con zonas vacías;
* fecha de creación;
* última modificación.

Ordenaciones:

* nombre;
* más reciente;
* última modificación;
* número de contenidos.

## 6.4 Copias de seguridad

Filtros recomendados:

* fecha;
* resultado;
* automática o manual;
* completa o configuración;
* destino;
* tamaño;
* válida o fallida.

Ordenaciones:

* más reciente;
* más antigua;
* mayor tamaño;
* duración.

## 6.5 Almacenamiento

Filtros recomendados:

* categoría;
* tamaño;
* recuperable;
* utilizado;
* no utilizado;
* antigüedad;
* archivos con problemas.

## 6.6 Registros y errores

Cuando existan listados de actividad o errores:

* nivel;
* tipo;
* fecha;
* operación;
* usuario;
* resuelto o pendiente.

No mostrar detalles técnicos sensibles al administrador.

---

# 7. Revisión de rendimiento

Realiza una auditoría específica de rendimiento antes y después de los cambios.

Crear:

```text
docs/PERFORMANCE_UX_AUDIT_PHASE_2.md
```

El documento debe recoger:

* problemas detectados;
* impacto;
* cambio aplicado;
* resultado;
* asuntos pospuestos.

## 7.1 Consultas de base de datos

Revisar:

* consultas N+1;
* relaciones cargadas innecesariamente;
* contadores recalculados por fila;
* búsquedas sin índices;
* filtros sobre columnas sin índice;
* consultas de uso multimedia;
* consultas del dashboard;
* consultas del editor visual.

Aplicar:

* eager loading selectivo;
* `withCount` cuando resulte apropiado;
* índices para claves foráneas;
* índices para estados y fechas filtradas frecuentemente;
* índices para hash de archivo;
* consultas agregadas;
* cachés de corta duración para métricas costosas.

No añadir índices indiscriminadamente. Documentar los nuevos.

## 7.2 Biblioteca multimedia

Garantizar fluidez con al menos 500 recursos.

Aplicar:

* paginación o carga progresiva;
* miniaturas optimizadas;
* no cargar vídeos completos;
* dimensiones adecuadas de miniaturas;
* caché HTTP;
* carga diferida;
* cancelación de búsquedas anteriores;
* debounce en buscador;
* placeholders mientras cargan;
* evitar regenerar miniaturas existentes.

## 7.3 Editor visual

Revisar:

* renders completos innecesarios;
* eventos excesivos durante arrastre;
* peticiones de autoguardado;
* reordenaciones;
* apertura del inspector;
* carga de biblioteca;
* vista previa.

Aplicar:

* actualizaciones parciales;
* claves estables;
* debounce;
* guardado por lotes;
* carga diferida del inspector;
* actualización optimista con recuperación ante error;
* reutilización de miniaturas.

No reproducir vídeos dentro de todas las zonas del editor.

## 7.4 Métricas de almacenamiento

No recorrer directorios grandes en cada carga del dashboard.

Utilizar:

* snapshots;
* caché;
* actualización incremental;
* análisis profundo programado;
* informe del host;
* invalidación después de operaciones relevantes.

## 7.5 Backups

Evitar que la creación de una copia bloquee peticiones web.

Usar el sistema de trabajos existente.

Si no existe infraestructura de colas:

* valorar una cola basada en base de datos;
* no introducir Redis únicamente para esta función;
* documentar la decisión;
* mantener un sistema recuperable tras reinicio.

## 7.6 Logs

Revisar:

* crecimiento;
* mensajes repetidos;
* payloads excesivos;
* excepciones generadas en bucle;
* logs de heartbeat;
* logs de autoguardado.

Reducir ruido sin perder capacidad de diagnóstico.

---

# 8. Revisión de experiencia de usuario

Además de implementar los requisitos, analiza posibles mejoras de uso.

No aplicar cambios radicales de diseño que contradigan Simple View.

## 8.1 Consistencia

Revisar:

* nombres de acciones;
* iconos;
* colores de estados;
* confirmaciones;
* mensajes de error;
* estados vacíos;
* menús contextuales;
* colocación de filtros;
* botones primarios y secundarios.

Usar siempre la misma terminología:

* Contenido.
* Diseño.
* Plantilla.
* Zona.
* Vista previa.
* Publicar.
* Eliminar.
* Copia de seguridad.

Evitar alternar entre términos como recurso, medio, asset o layout en la interfaz.

## 8.2 Eliminaciones

Las confirmaciones deben explicar consecuencias reales.

Evitar mensajes genéricos como:

```text
¿Está seguro?
```

Usar mensajes como:

```text
Vas a eliminar definitivamente “Vídeo escaparate.mp4”.

No se está utilizando en ningún diseño.
Se liberarán 820 MB.
```

Cuando una acción esté bloqueada, explicar cómo resolverlo.

## 8.3 Feedback

Toda operación debe mostrar:

* progreso cuando tarde;
* éxito;
* error;
* resultado parcial;
* siguiente acción recomendada.

No dejar botones bloqueados sin explicación.

## 8.4 Estados vacíos

Ejemplos:

```text
No hay contenidos no utilizados.
Tu biblioteca está optimizada.
```

```text
No hay copias de seguridad todavía.
La primera copia se realizará hoy a las 03:00.
```

```text
No hay diseños inactivos que se puedan eliminar.
```

## 8.5 Acciones masivas

Cuando exista selección múltiple:

* mostrar número seleccionado;
* mostrar espacio estimado;
* permitir limpiar selección;
* mantener la selección al paginar solo cuando sea seguro;
* presentar el resultado individual.

## 8.6 Prevención de errores

Añadir advertencias antes de:

* eliminar contenido;
* eliminar diseño;
* publicar con zonas vacías;
* cambiar una plantilla;
* crear una copia sin espacio;
* configurar un destino no disponible.

No abusar de diálogos de confirmación para acciones reversibles.

## 8.7 Dashboard

Revisar que el dashboard muestre solo información accionable:

* estado de la pantalla;
* diseño activo;
* almacenamiento;
* próxima copia;
* última copia;
* alertas;
* accesos a editor visual, contenidos y almacenamiento.

Retirar accesos al editor clásico.

---

# 9. Seguridad y permisos

Mantener el sistema de administrador único existente.

Todas las acciones de eliminación, backup y configuración deben requerir autenticación.

Proteger contra:

* manipulación de identificadores;
* eliminación de recursos ajenos al almacenamiento gestionado;
* path traversal;
* eliminación mediante rutas enviadas por el navegador;
* solicitudes duplicadas;
* CSRF;
* ejecución simultánea de la misma operación;
* borrado de archivos fuera de los directorios permitidos.

Las rutas físicas nunca deben aceptarse directamente desde el cliente.

---

# 10. Migraciones y compatibilidad

Las migraciones deben:

* conservar todos los diseños;
* conservar publicaciones;
* conservar recursos;
* conservar configuración;
* añadir índices de forma segura;
* poder ejecutarse sobre instalaciones existentes;
* evitar cambios destructivos.

Antes del despliegue:

1. Crear backup.
2. Ejecutar migraciones.
3. Validar datos.
4. Ejecutar pruebas de salud.
5. Mantener posibilidad de rollback.

No eliminar tablas del editor clásico hasta verificar que no contienen datos exclusivos.

Cuando contengan datos necesarios, migrarlos al modelo utilizado por el editor visual.

---

# 11. Pruebas

Añadir pruebas automáticas para los siguientes escenarios.

## 11.1 Contenidos

* eliminar contenido no utilizado;
* bloquear contenido de publicación activa;
* bloquear imagen de respaldo;
* ver usos;
* retirar de diseños inactivos y eliminar;
* eliminación múltiple parcial;
* archivo físico inexistente;
* error de eliminación;
* actualización de métricas;
* eliminación desde selector;
* eliminación desde almacenamiento.

## 11.2 Diseños

* eliminar diseño inactivo;
* bloquear diseño activo;
* bloquear diseño de respaldo;
* eliminar diseño abierto;
* no eliminar multimedia asociada;
* mantener al menos un diseño válido;
* eliminación múltiple parcial.

## 11.3 Editor

* todas las acciones abren el editor visual;
* rutas antiguas redirigen;
* el editor clásico no aparece en navegación;
* no quedan enlaces rotos;
* publicación y vista previa siguen funcionando.

## 11.4 Backups

* frecuencia diaria;
* frecuencia cada dos días;
* rechazo de intervalo superior a 48 horas;
* ejecución recuperada tras apagado;
* cálculo de próxima copia;
* prueba de destino;
* falta de espacio;
* retención segura;
* no eliminación de la última copia válida.

## 11.5 Filtros

* combinación de filtros;
* limpieza;
* persistencia;
* paginación;
* ordenación;
* filtros sin resultados;
* protección frente a parámetros inválidos.

## 11.6 Rendimiento

Añadir pruebas o mediciones razonables para:

* biblioteca con 500 recursos;
* listado de diseños;
* consultas sin N+1;
* autoguardado;
* métricas cacheadas;
* eliminación múltiple.

No crear pruebas frágiles que dependan exclusivamente de tiempos exactos.

---

# 12. Criterios de aceptación

La tarea se considerará terminada cuando:

1. El editor visual sea el editor principal y único visible.
2. El editor clásico no aparezca en navegación ni flujos normales.
3. Los enlaces antiguos redirijan correctamente.
4. Sea posible eliminar contenido desde el selector multimedia.
5. Sea posible eliminar contenido desde gestión de almacenamiento.
6. La aplicación bloquee contenido usado por la publicación activa.
7. Puedan retirarse y eliminarse contenidos usados solo por diseños inactivos.
8. La eliminación muestre el espacio liberado.
9. Sea posible eliminar diseños inactivos.
10. No sea posible eliminar el diseño que se reproduce.
11. Eliminar un diseño no elimine sus archivos multimedia.
12. La configuración de backups permita diario o cada dos días.
13. No sea posible configurar un intervalo superior a 48 horas.
14. Se muestre última y próxima copia.
15. Se recuperen ejecuciones perdidas tras un apagado.
16. Todos los listados relevantes tengan buscador, filtros, ordenación y paginación.
17. Los filtros sean comprensibles y combinables.
18. La biblioteca funcione con fluidez con al menos 500 contenidos.
19. No existan consultas N+1 relevantes en los nuevos listados.
20. El dashboard muestre información accionable.
21. Las operaciones destructivas tengan mensajes claros.
22. La suite de pruebas anterior continúe superándose.
23. Exista documentación de rendimiento y experiencia de usuario.
24. No se pierdan publicaciones, diseños, contenidos ni configuraciones existentes.

---

# 13. Entregables

Entregar:

* implementación completa;
* migraciones;
* servicios de eliminación;
* acciones desde selector y almacenamiento;
* eliminación segura de diseños;
* retirada del editor clásico;
* redirecciones de compatibilidad;
* configuración visual de backups;
* filtros y ordenaciones;
* mejoras de rendimiento;
* mejoras de experiencia de usuario;
* pruebas;
* actualización de documentación;
* informe `docs/PERFORMANCE_UX_AUDIT_PHASE_2.md`;
* instrucciones de despliegue;
* instrucciones de rollback;
* resumen de archivos modificados;
* lista de decisiones técnicas;
* lista de mejoras futuras no incluidas.

Trabaja mediante cambios pequeños y verificables.

Después de cada bloque:

1. Ejecuta las pruebas relacionadas.
2. Corrige regresiones.
3. Comprueba la reproducción.
4. Comprueba la vista previa.
5. Comprueba la publicación.
6. Comprueba que no haya pérdida de datos.

No des por finalizada la tarea únicamente porque la interfaz sea visible. Verifica reglas de negocio, persistencia, almacenamiento, seguridad y recuperación ante errores.
