# Reglas y Contexto del Proyecto — seventech-trivia

- **Nombre del proyecto:** `seventech-trivia`
- **Ruta del proyecto:** `/home/vaxer/seventech-trivia`

## Uso de Memoria MCP (Knowledge Graph)

- Usa el servidor MCP `memory` (Knowledge Graph) al inicio de las sesiones y durante el trabajo para consultar y almacenar entidades, relaciones y decisiones clave del proyecto.
- Prioriza consultar el Knowledge Graph antes de explorar o re-leer archivos innecesariamente, con el objetivo de ahorrar tokens y mantener continuidad entre sesiones.
- Actualiza el Knowledge Graph cuando descubras o modifiques arquitectura, modelos, controladores, convenciones o decisiones importantes.

## Reglas Técnicas Clave

- **Ordenamiento de preguntas de trivia:** Las preguntas se ordenan de forma pseudoaleatoria e idéntica usando `semilla_partida` mediante `CRC32(CONCAT(id, ?))` (implementado en `app/Http/Controllers/ConcursanteController.php`).

## Reglas Permanentes del Proyecto

- **Flujo de ramas y commits:** Trabajar sobre la rama `desarrollo` creando ramas por bloque (`bloque-X-...` desde `desarrollo`). Commits pequeños con mensajes descriptivos. Prohibido hacer `push` o `merge` a `main` sin orden explícita del usuario.
- **Base de datos de pruebas:** Ejecutar tests **exclusivamente** contra `seventech_trivia_test` (protegido por `tests/TestCase.php`). Nunca ejecutar pruebas ni comandos destructivos contra `seventech_trivia`.
- **Seguridad de archivos y secretos:** Prohibido borrar archivos sin listarlos antes y recibir confirmación explícita del usuario. No leer ni modificar `.env` ni subir secretos al repositorio. No modificar `composer.json` ni `composer.lock` sin aviso previo.
- **Stack inmutable:** Mantener Laravel 13 (`PHP ^8.3`), MySQL 8, vistas Blade + Tailwind por CDN + JavaScript Vanilla con HTTP Short Polling (sin introducir React ni otros frameworks SPA).
- **Documentación viva:** Al finalizar cada bloque, entregar reporte de archivos modificados, resultado de tests, riesgos abiertos y actualizar `docs/ESTADO.md`.


