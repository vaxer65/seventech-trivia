# Reglas y Contexto del Proyecto — seventech-trivia

- **Nombre del proyecto:** `seventech-trivia`
- **Ruta del proyecto:** `/home/vaxer/seventech-trivia`

## Uso de Memoria MCP (Knowledge Graph)

- Usa el servidor MCP `memory` (Knowledge Graph) al inicio de las sesiones y durante el trabajo para consultar y almacenar entidades, relaciones y decisiones clave del proyecto.
- Prioriza consultar el Knowledge Graph antes de explorar o re-leer archivos innecesariamente, con el objetivo de ahorrar tokens y mantener continuidad entre sesiones.
- Actualiza el Knowledge Graph cuando descubras o modifiques arquitectura, modelos, controladores, convenciones o decisiones importantes.

## Reglas Técnicas Clave

- **Ordenamiento de preguntas de trivia:** Las preguntas se ordenan de forma pseudoaleatoria, uniforme e idéntica para todos los equipos usando `semilla_partida` mediante `CRC32(SHA2(CONCAT(preguntas.id, "-", ?), 256))` (implementado en `app/Http/Controllers/ConcursanteController.php` y `app/Http/Controllers/AdminController.php`).
- **Barajado determinista y opaco de opciones A/B/C/D:** En `GET /api/estado-juego`, el servidor permuta los textos `opcion_a..d` usando una de las 24 permutaciones de $S_4$ derivada de `hash('sha256', "{$equipoId}:{$preguntaId}:{$semilla}")` y en `POST /api/enviar-respuesta` traduce la letra visual elegida a la clave original antes de calificar.
- **Banco multitemático de 360 preguntas y 1 pregunta amena por etapa:** El seeder siembra 10 módulos tecnológicos y 360 preguntas originales (110 Etapa 1, 110 Etapa 2, 100 Etapa 3, 40 Muerte Súbita) con distribución exacta 25% por opción (`90 a..d`) y `modulo_activo_id = null` (Modo *"Todos los Módulos"*) por defecto. Cada etapa normal entrega exactamente 1 pregunta amena (`es_amena = true`) en el turno determinista `hexdec(substr(hash('sha256', "amena:{$nivel->id}:{$semilla}"), 0, 8)) % $nivel->cantidad_preguntas` y el resto técnicas (`es_amena = false`). Soporta importación masiva CSV/JSON con deduplicación en `POST /admin/preguntas/importar`.

## Reglas Permanentes del Proyecto

- **Flujo de ramas y commits:** Trabajar sobre la rama `desarrollo` creando ramas por bloque (`bloque-X-...` desde `desarrollo`). Commits pequeños con mensajes descriptivos. Prohibido hacer `push` o `merge` a `main` sin orden explícita del usuario.
- **Base de datos de pruebas:** Ejecutar tests **exclusivamente** contra `seventech_trivia_test` (protegido por `tests/TestCase.php`). Nunca ejecutar pruebas ni comandos destructivos contra `seventech_trivia`.
- **Seguridad de archivos y secretos:** Prohibido borrar archivos sin listarlos antes y recibir confirmación explícita del usuario. No leer ni modificar `.env` ni subir secretos al repositorio. No modificar `composer.json` ni `composer.lock` sin aviso previo.
- **Stack inmutable:** Mantener Laravel 13 (`PHP ^8.3`), MySQL 8, vistas Blade + Tailwind por CDN + JavaScript Vanilla con HTTP Short Polling (sin introducir React ni otros frameworks SPA).
- **Documentación viva:** Al finalizar cada bloque, entregar reporte de archivos modificados, resultado de tests, riesgos abiertos y actualizar `docs/ESTADO.md`.


