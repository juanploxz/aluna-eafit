# Editar y verificar ALUNA

## Contenido editable

En **Productos ALUNA**, editar nombre, extracto, categoría, precio en COP, presentación, tipos de piel, ingredientes destacados, uso, etiqueta y paso de la rutina. La casilla de destacado controla la selección principal. El campo Orden permite organizar los productos. Una imagen destacada reemplaza la imagen de colección incluida. Guardar como borrador retira el producto del catálogo público.

En **Apariencia → Personalizar → ALUNA · Contenido de inicio**, editar los textos de portada y de la empresa. Las opciones se guardan en la base de datos de WordPress y se comparten entre las dos instancias.

Los ocho productos de muestra pueden cargarse desde **Productos ALUNA → Preparar catálogo**. Repetir esta acción conserva las modificaciones: solo crea los slugs que no existen.

## Vista previa local

Requisitos: Node.js 24.18 o posterior, npm 11.16 o posterior y acceso a Internet para la primera descarga. Desde la carpeta `aluna`:

```sh
npm ci --prefix scripts
node scripts/preview.mjs
```

Abrir `http://127.0.0.1:8090`. El panel `/wp-admin/` usa `admin` y `password`, exclusivos de esta demostración local. El runtime y su base SQLite quedan en `.preview/`; no se incluyen en la aplicación de producción. Detener con Ctrl+C. Las ediciones de archivos se reflejan al recargar. La primera preparación tarda más que las siguientes.

Puede elegirse otro puerto mediante la variable `PORT`. `ALUNA_RUNTIME` permite colocar las dependencias y archivos de desarrollo fuera de la entrega. Al terminar el arranque, el script vuelve a enlazar el servidor a `127.0.0.1`. La versión 3.1.65 de Playground inicia previamente su listener en todas las interfaces y no ofrece una opción pública para restringir ese enlace inicial: ejecutar la vista previa en un equipo con firewall que bloquee conexiones entrantes, especialmente porque usa credenciales de demostración. No se afirma aislamiento de red durante todo el arranque. SQLite sirve para desarrollo: AWS debe usar MariaDB según la configuración elegida para el curso.

## Pruebas automáticas

Preparar primero la vista previa una vez para descargar WordPress. Después, desde la carpeta `aluna`:

```sh
node tests/integration.mjs
node --test tests/frontend.test.cjs
```

El runner usa una copia de WordPress con una base de prueba y el puerto 8091. Verifica registro de productos, importación repetible, borradores ocultos, saneamiento persistido, protección de edición, API anónima, respuesta de salud, identidad por petición, caché, portada, 404 y carga de recursos. Guarda la última ejecución satisfactoria en `tests/results.json`.

Para una comprobación adicional con PHP nativo:

```sh
php wp-content/plugins/aluna-core/tests/unit.php
find wp-content/themes/aluna wp-content/plugins/aluna-core -name '*.php' -print0 | xargs -0 -n1 php -l
```

Los tests de integración del plugin crean y eliminan un producto temporal; ejecutarlos únicamente sobre la base de prueba. El runner los aísla del catálogo de la vista previa.

## Revisión de interfaz antes de la presentación

1. Abrir portada en móvil y escritorio; verificar navegación por teclado, foco visible, menú y enlaces.
2. Filtrar productos, buscar un nombre y probar una búsqueda sin coincidencias; limpiar filtros.
3. Abrir una ficha, consultar ingredientes y uso, guardar un favorito y recargar.
4. Completar la guía de rutina, cambiar elecciones y reiniciarla.
5. Consultar las tres sedes y la información de la empresa.
6. Editar un producto en WordPress y comprobar que la modificación aparece en el catálogo.
7. En AWS, refrescar la portada a través de cada balanceador y comprobar la IP privada y el identificador de cada respuesta.

El informe de resultados registra comprobaciones concretas; no es una garantía de ausencia absoluta de errores ni una prueba de capacidad o alta disponibilidad en AWS.
