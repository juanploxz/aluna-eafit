# Verificación de ALUNA

Fecha: 17 de noviembre de 2026. La ejecución de integración del tema actualizado terminó a las 20:47, hora de Bogotá. Evidencia reproducible: `tests/results.json` y los runners incluidos.

| Verificación ejecutada | Resultado |
| --- | --- |
| Parseo PHP con `TOKEN_PARSE` | 15 archivos sin errores de sintaxis. |
| Reglas PHP de identidad y sanitización | 39 aserciones aprobadas. |
| Integración con WordPress real | 21 comprobaciones aprobadas. |
| Runner completo PHP + HTTP + recursos | 63 comprobaciones aprobadas; incluye los grupos anteriores. |
| Lógica de filtros, búsqueda, rutina y favoritos | 46 pruebas Node aprobadas. |
| Sintaxis de `app.js`, `logic.js` e `integration.mjs` | `node --check` sin errores. |

El runner de 63 comprobaciones cuenta el grupo de 39 aserciones unitarias como una comprobación; los valores de la tabla no deben sumarse como si fueran pruebas independientes.

## Entorno utilizado

WordPress 7.1.4 oficial, PHP 8.3.36 mediante WordPress Playground 3.1.65, Node 24.22.3 y SQLite para la verificación local. La ejecución indicada incluye el cambio de visibilidad del diagnóstico. No se instaló un CMS alternativo ni se sustituyó WordPress con una maqueta HTML.

Se ejecutaron operaciones reales sobre una base de datos de prueba: carga repetida del catálogo, creación de borrador, publicación, cambios de metadatos y eliminación del producto temporal. Se comprobó que un nonce inválido impide modificar una ficha, un editor autorizado puede guardar cambios y un visitante anónimo no puede escribir mediante REST. También se probaron valores malformados, precios negativos, rutas de imagen no permitidas y filtros de IPv4 privada.

Por HTTP se verificaron la portada, las ocho fichas de producto, el catálogo REST, la página 404 y los recursos CSS, JavaScript, SVG y WebP. La portada devolvió HTTP 200 con idioma `es-CO`, sin advertencias PHP expuestas, con `no-store` y un identificador distinto en solicitudes sucesivas. Los endpoints de nodo y salud devolvieron respuestas válidas; salud comprobó la base de datos.

La portada habitual no incluyó el indicador técnico ni el elemento `#server-ip`. La vista `/?aluna-diagnostico=1` mostró una IP coincidente con la cabecera de su respuesta y el mismo identificador en `data-request-id`, manteniendo `no-store`. Los valores `0`, `true` y de tipo arreglo no activaron el indicador ni produjeron advertencias PHP. Las ocho fichas se sirvieron sin indicador; también se comprobó su ausencia en una ficha solicitada con el parámetro de diagnóstico.

Las pruebas frontend cubren lógica pura: búsqueda sin sensibilidad a tildes, combinaciones de filtros, ordenamiento, selección de una rutina compatible, favoritos corruptos o antiguos y resultados vacíos. No ejercitan eventos DOM, el foco ni la representación visual del navegador.

## Alcance pendiente de verificar

- **Revisión visual y navegación en navegador:** no ejecutadas. El acceso de automatización al origen local fue rechazado; se respetó esa restricción y no se utilizó una vía alternativa. Revisar la lista de comprobaciones de `EDICION-Y-PRUEBAS.md` en escritorio y móvil antes de la presentación.
- **MariaDB, Apache/NGINX y Ubuntu reales:** no disponibles en esta sesión. El código usa las APIs nativas de WordPress y consultas compatibles, pero la ejecución SQLite no certifica la configuración de producción.
- **Dos nodos y balanceadores:** no desplegados. La dirección que el runtime local proporciona no constituye evidencia de alternancia entre instancias EC2. Ejecutar B1 y B2 con las IP privadas reales en `/?aluna-diagnostico=1`. La portada ordinaria ya no muestra la IP por solicitud del usuario; no se afirma que esa vista sin parámetro cumpla por sí sola el requisito visual del enunciado.
- **DNS, NAT, carga concurrente y recuperación de fallas:** corresponden a la infraestructura del compañero. No se afirman resultados de esas pruebas.
- **Rendimiento visual, accesibilidad auditada y métricas de Lighthouse:** no medidos. La implementación incluye HTML semántico, foco visible, diálogos nativos, movimiento reducido y diseño adaptable; la revisión de esos comportamientos en un navegador permanece pendiente.

La evidencia acredita los flujos y las condiciones indicadas. No equivale a una garantía de ausencia absoluta de errores ni a una certificación de alta disponibilidad del despliegue.

## Repetir las pruebas

```sh
# Node >=24.18, npm >=11.16; desde la carpeta aluna.
npm ci --prefix scripts
node scripts/preview.mjs
# Detener la vista previa con Ctrl+C una vez preparada, o abrir otra terminal.
node tests/integration.mjs
node --test tests/frontend.test.cjs
node --check wp-content/themes/aluna/assets/js/app.js
node --check wp-content/themes/aluna/assets/js/logic.js
```

Los puertos por defecto son 8090 para la vista previa y 8091 para pruebas. Las dependencias se instalan exclusivamente como herramientas de desarrollo. No se necesitan npm, Playground, SQLite ni los tests para servir el sitio WordPress en AWS.
