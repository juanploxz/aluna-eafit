# ALUNA

Aplicación web de cuidado personal para XYZ S.A. · Proyecto SI3002, EAFIT.

**WordPress administra el contenido.** El tema original y el plugin incluidos entregan catálogo, fichas de producto, búsqueda y filtros, favoritos locales, guía de rutina e información de la empresa y sus tres sedes. La portada muestra la IPv4 privada real de la instancia que respondió.

## Probar en tu equipo

Con **Node.js 24.18 o posterior** y **npm 11.16 o posterior** (requisito declarado del runtime de desarrollo), desde esta carpeta:

```sh
npm ci --prefix scripts
node scripts/preview.mjs
```

Abre **http://127.0.0.1:8090/**. Administración local: **/wp-admin/**, usuario `admin`, contraseña `password`. Estas credenciales son solo del entorno de demostración, aislado en tu equipo. La primera ejecución descarga WordPress y el runtime; las siguientes reutilizan los archivos. Detén con **Ctrl+C**.

La vista previa utiliza WordPress real y PHP con SQLite. **La entrega del curso usa MariaDB**, compartida por las dos instancias; Node no es necesario en producción.

## Instalar en WordPress

1. Instala y activa `aluna-core.zip` desde **Plugins → Añadir plugin → Subir plugin**.
2. Instala y activa `aluna-theme.zip` desde **Apariencia → Temas → Añadir tema → Subir tema**.
3. Abre **Productos ALUNA → Preparar catálogo** y carga los ocho productos de muestra.
4. Guarda **Ajustes → Enlaces permanentes** y selecciona español de Colombia en los ajustes generales.

Los ZIP instalables están en `packages/`. También puedes copiar las carpetas de `wp-content/` directamente. El procedimiento para los dos nodos y la configuración de IP están en [la guía de entrega](docs/ENTREGA.md).

## Editar y comprobar

- **Productos ALUNA:** nombre, texto, precio, imagen, categoría, piel, uso e ingredientes.
- **Apariencia → Personalizar → ALUNA:** textos de portada y de la empresa.
- **[Edición y pruebas](docs/EDICION-Y-PRUEBAS.md):** comandos y recorrido de revisión.
- **[Verificación realizada](docs/VERIFICACION.md):** resultados y límites de las pruebas.
- **[Imágenes originales](docs/IMAGENES.md):** procedencia y prompts.

Pruebas de lógica, sin instalar dependencias:

```sh
node --test tests/frontend.test.cjs
```

Pruebas de WordPress, después de preparar la vista previa:

```sh
node tests/integration.mjs
```

La app no procesa compras. El contenido comercial es demostrativo. La infraestructura AWS, el montaje y las pruebas de red quedan a cargo del compañero de despliegue, conforme al alcance solicitado.
