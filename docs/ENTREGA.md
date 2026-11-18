# ALUNA · Entrega de la aplicación

ALUNA es una marca ficticia de cuidado personal de XYZ S.A. para el proyecto SI3002 de EAFIT. El sitio informa sobre la empresa, sus productos y su presencia en Medellín, Bogotá y Bucaramanga. Los precios, fórmulas e imágenes son contenido de demostración. No procesa ventas ni recoge datos personales en formularios.

## Componentes

| Componente | Función |
| --- | --- |
| `wp-content/themes/aluna` | Presentación responsive, catálogo, fichas, favoritos y guía de rutina. |
| `wp-content/plugins/aluna-core` | Modelo de productos, controles editoriales, importación idempotente y diagnóstico por petición. |
| `scripts/preview.mjs` | WordPress real en PHP WebAssembly para desarrollo local. |
| `tests/integration.mjs` | Pruebas de integración y respuestas HTTP con una base de datos de prueba. |

El CMS y la fuente de verdad del catálogo son WordPress. El navegador no contiene una base de datos alternativa. El tema puede cambiar sin perder los productos, porque el modelo reside en el plugin. La interfaz usa CSS y JavaScript nativos; no requiere un proceso Node en producción.

## Instalación sobre WordPress

Entorno de referencia verificado: WordPress 7.1.4 y PHP 8.3.36. Para el despliegue del curso, usar Apache o NGINX con PHP 8.3 y una base de datos MariaDB 10.11 compartida. WordPress soporta MariaDB de forma nativa; PostgreSQL exigiría una capa de compatibilidad no incluida en este proyecto. Mantener las actualizaciones de seguridad de WordPress y del sistema.

1. Instalar WordPress en las dos instancias usando la misma base de datos, prefijo de tablas, URL pública y claves/salts de autenticación.
2. Copiar `aluna-core` a `wp-content/plugins/` y `aluna` a `wp-content/themes/` en ambas instancias. Mantener exactamente la misma versión de código y recursos.
3. Activar **ALUNA Core** y el tema **ALUNA** una sola vez desde la administración compartida.
4. En **Productos ALUNA → Preparar catálogo**, pulsar **Cargar catálogo de muestra**. La operación no reemplaza productos existentes ni borra ediciones.
5. En **Ajustes → Generales**, seleccionar español de Colombia y la zona horaria Bogotá. En **Lectura**, conservar la portada con las últimas entradas: el tema proporciona `front-page.php`.
6. Guardar **Ajustes → Enlaces permanentes**. Habilitar las reglas de reescritura habituales de WordPress en ambos servidores.
7. Verificar portada, fichas, `/?aluna-diagnostico=1`, `/wp-json/aluna/v1/health` y `/wp-json/aluna/v1/node` a través de cada balanceador.

Alternativa con WP-CLI, ejecutada desde la instalación de WordPress:

```sh
wp plugin activate aluna-core
wp theme activate aluna
wp aluna seed
wp option update timezone_string America/Bogota
wp option update WPLANG es_CO
wp option update show_on_front posts
wp rewrite structure '/%postname%/'
wp rewrite flush
```

No se incluye un usuario de producción ni una contraseña de despliegue. Crearlos durante la instalación. Las credenciales `admin/password` pertenecen únicamente al entorno local aislado de Playground y nunca deben usarse en AWS.

## Identidad real de cada servidor

La portada habitual no muestra información del servidor. Para la demostración técnica, abrir **`/?aluna-diagnostico=1`**: únicamente en esa vista de la portada se muestra la IPv4 privada calculada en PHP. El valor debe ser exactamente la cadena `1`; `0`, `true` y parámetros de tipo arreglo no activan el diagnóstico. Las fichas de producto no muestran el indicador, incluso con el parámetro.

En la vista de diagnóstico, el identificador de la petición está en el atributo `data-request-id` del elemento `#server-ip`. Las cabeceras `X-Aluna-Node` y `X-Aluna-Request-ID` siguen presentes en las respuestas de la portada normal y de diagnóstico; la hora se consulta en `/wp-json/aluna/v1/node`. La identidad se calcula una vez por petición. No hay un contador de alternancia en JavaScript ni direcciones inventadas.

**Alcance frente al enunciado:** el PDF solicita una IP privada visible y dinámica en la página principal. Por solicitud del usuario, la portada ordinaria ya no la muestra. Para B1 y B2 se debe demostrar la página principal con `?aluna-diagnostico=1` y conservar ese parámetro en las evidencias. No se afirma que la vista ordinaria sin parámetro satisfaga por sí sola ese requisito visual.

La resolución acepta únicamente RFC1918 y consulta, en orden: constante `ALUNA_NODE_IP`, variable de entorno del mismo nombre, `SERVER_ADDR` y resolución del hostname local. Si el entorno no permite determinar una IPv4 privada, la interfaz lo indica. En cada instancia puede fijarse su IP real en el `wp-config.php` local:

```php
// Sustituir por la IPv4 privada REAL de esta instancia, distinta en el otro nodo.
define('ALUNA_NODE_IP', getenv('ALUNA_NODE_IP') ?: '');
define('DISALLOW_FILE_EDIT', true);
```

Pasar la variable al proceso PHP/Apache/PHP-FPM; una variable de una sesión SSH no necesariamente llega al servidor web. No guardar la IP en una opción de WordPress compartida, ni copiar el valor del nodo A al nodo B. El entorno WebAssembly local puede resolver una dirección virtual; esa dirección no demuestra balanceo en AWS.

## Caché y balanceo

La portada y los endpoints de diagnóstico envían `Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private`, `Pragma`, `Surrogate-Control` y `CDN-Cache-Control`. La portada también declara `DONOTCACHEPAGE`. Los archivos CSS, JavaScript y WebP pueden cachearse normalmente.

Los balanceadores y cualquier plugin o proxy de caché deben respetar esas cabeceras y excluir la portada y `/wp-json/aluna/v1/` de su caché. Una caché de página que responde antes de ejecutar WordPress no puede ser controlada por el tema. No habilitar afinidad de sesión para las pruebas de distribución.

```sh
# En B1/B2, abrir también estas URLs en el navegador para registrar la IP visible.
curl -sS -D - 'http://DOMINIO/?aluna-diagnostico=1&verificacion=1'
curl -sS -D - 'http://DOMINIO/?aluna-diagnostico=1&verificacion=2'
curl -sS 'http://DOMINIO/wp-json/aluna/v1/node'
curl -sS 'http://DOMINIO/wp-json/aluna/v1/health'
```

Cuando WordPress logra inicializarse, `health` consulta la base de datos y devuelve HTTP 200 con `{"status":"ok"}` o HTTP 503 con `{"status":"unavailable"}`. Si la base falla antes de que WordPress cargue el plugin, puede responder un error de arranque, por ejemplo HTTP 500, o no responder. Configurar el balanceador para considerar sano únicamente HTTP 200 con `status: ok`; cualquier otro estado o timeout debe marcar el nodo como no disponible. El endpoint no devuelve credenciales, hostname ni configuración interna.

## Estado y archivos compartidos

- Productos, categorías, ajustes y usuarios viven en la MariaDB común.
- Los archivos del tema y del plugin se distribuyen de forma idéntica a los dos nodos. Los recursos incluidos son locales: el sitio no depende de CDNs de fuentes ni de un servidor de imágenes remoto para verse correctamente.
- Si se cambian imágenes desde la biblioteca de medios, `wp-content/uploads` debe estar compartido o sincronizado entre nodos antes de publicar esos cambios. Una base de datos común no replica archivos. El compañero de infraestructura debe escoger y documentar el mecanismo apropiado sin exceder las nueve instancias del enunciado.
- Los favoritos y las respuestas de la guía de rutina permanecen en el navegador. No hay sesiones PHP propias ni necesidad de afinidad para el catálogo público.
- WordPress usa cookies firmadas para autenticación. Compartir salts, dominio y configuración entre nodos; verificar acceso a la administración a través de ambos balanceadores.
- La instancia única de MariaDB exigida por el enunciado sigue siendo un punto único de falla. La app no elimina esa limitación de la arquitectura. Mantener copias de seguridad y probar la restauración corresponde al despliegue.

## Seguridad y operaciones

Las ediciones requieren capacidades de WordPress y nonce. Los metadatos se validan y sanitizan en el servidor; los textos se escapan al renderizar. Las escrituras anónimas del API de productos se rechazan. Evitar instalar plugins innecesarios, conceder el mínimo acceso editorial y servir HTTPS en el dominio público. Si HTTPS termina en un proxy, normalizar `X-Forwarded-Proto` solo desde proxies de confianza antes de configurar WordPress para reconocerlo; no confiar en cabeceras enviadas directamente por cualquier cliente.

El catálogo y las fichas básicas se renderizan en el servidor y siguen disponibles sin JavaScript. Las funciones de favoritos, filtros instantáneos y guía interactiva requieren JavaScript. El sitio no presenta favoritos locales como datos sincronizados entre dispositivos.

## Qué queda en manos del despliegue

VPC, cuatro subredes, nueve EC2, NAT Linux, DNS BIND9 primario/secundario, dos balanceadores, TLS y reglas de red. Las pruebas de failover, distribución real entre nodos, DNS y NAT deben ejecutarse sobre esa infraestructura. La verificación local de la aplicación no reemplaza esas evidencias.

## Referencias consultadas

- [Requisitos de WordPress](https://wordpress.org/about/requirements/).
- [Seguridad de temas: validación, sanitización y escape](https://developer.wordpress.org/themes/advanced-topics/security/).
- [WordPress Playground CLI](https://developer.wordpress.org/playground/developers/local-development/wp-playground-cli/).
- [Uso programático del runtime de pruebas](https://developer.wordpress.org/playground/handbook/guides/programmatic-playground-cli/).
