# Estado del Proyecto — SevenTech Trivia

## 1. Estado Actual

- **Rama activa:** `bloque-2-algoritmo-banco` (creada desde `desarrollo` tras integrar `bloque-1-reinicio` en `e561ecc`).
- **Entorno en producción (cPanel):** `https://trivia-seventech.softecsa.com` operativo.
- **Suite de pruebas:** **18 tests pasando (620 aserciones)** contra `seventech_trivia_test`:
  - `Tests\Unit\ExampleTest` (1 test)
  - `Tests\Feature\ExampleTest` (1 test)
  - `Tests\Feature\Bloque1SeguridadTest` (10 tests: ocultación de respuesta correcta, rechazo de pregunta no asignada `409`, mismo orden por `semilla_partida`, tiempo medido en servidor, idempotencia `UNIQUE(equipo_id, pregunta_id)`, sesión por cookie sin `equipo_id` en request, límite 2–5 integrantes, `rate limiting` del PIN `429`, protección de `/admin/*` y regeneración de `semilla_partida`).
  - `Tests\Feature\Bloque1ReinicioTest` (3 tests: reinicio total a cero conservando Salón de la Fama, reinicio conservando equipos e integrantes con contadores en cero, y rechazo limpio `409`/`401` de respuestas o estados tardíos tras reinicio sin ensuciar la BD).
  - `Tests\Feature\Bloque2AlgoritmoBancoTest` (3 tests: inclusión sin sesgo de preguntas nuevas recién creadas en el sorteo `CRC32(SHA2(...))` con secuencia idéntica entre equipos, permutación determinista en recarga y diferente entre equipos totalmente opaca en JSON con traducción exacta en servidor, y distribución matemática uniforme ~25% en las posiciones visuales `A`, `B`, `C`, `D`).

---

## 2. Mapa y Checklist de Bloques (0 a 8)

- [x] **Bloque 0 (`bloque-0-limpieza`) — Completado:** Inventario y limpieza de archivos temporales/no usados, actualización de `.gitignore`, registro de correcciones de producción en `desarrollo`, alineación de [`docs/STACK.md`](file:///home/vaxer/seventech-trivia/docs/STACK.md) y [`docs/DESPLIEGUE.md`](file:///home/vaxer/seventech-trivia/docs/DESPLIEGUE.md), reglas permanentes en [`GEMINI.md`](file:///home/vaxer/seventech-trivia/GEMINI.md) y creación de [`docs/ESTADO.md`](file:///home/vaxer/seventech-trivia/docs/ESTADO.md).
- [x] **Bloque 1 (`bloque-1-reinicio`) — Completado:** Reinicio 100% atómico en `AdminController::reiniciarJuego()` sin `TRUNCATE` (`query()->delete()`), orden estricto de locks (`ConfiguracionEvento::lockForUpdate()` antes que `Equipo`), renovación garantizada de `semilla_partida`, blindaje contra peticiones en vuelo en `ConcursanteController` y preservación del Salón de la Fama (`historial_eventos`).
- [x] **Bloque 2 (`bloque-2-algoritmo-banco`) — Completado:** Sorteo uniforme de preguntas mediante `CRC32(SHA2(CONCAT(preguntas.id, "-", ?), 256))` tanto en juego real como en el simulador, rotación de `semilla_partida` también al iniciar desde `finalizado`, preselección del módulo activo al crear preguntas en `/admin`, y barajado determinista y opaco en servidor de las opciones `A/B/C/D` sobre las 24 permutaciones de $S_4$ por `(equipo_id, pregunta_id, semilla_partida)` con traducción inversa al calificar.
- [ ] **Bloque 3 — Pendiente:** Reemplazo de las 60 preguntas de prueba por el banco definitivo original (~200 preguntas de carrera repartidas 70/70/60 en fases 1/2/3 + ~30 de muerte súbita dificilísima).
- [ ] **Bloque 4 — Pendiente:** (a) Concursantes: detección y bloqueo en servidor al salir de la pestaña/app (pierde esa pregunta y espera a la siguiente) con registro de incidentes visible en `/admin`. (b) Admin: autoguardado con toasts y bloqueo de edición de parámetros críticos cuando el evento está `en_curso`, `pausado` o `desempate`.
- [ ] **Bloque 5 — Pendiente:** Sistema de rachas en servidor y UI (la racha se rompe con respuesta incorrecta, tiempo agotado o bloqueo por salir de la pestaña).
- [ ] **Bloque 6 — Pendiente:** QR dinámico, experiencia móvil y validación de conexión en red local y producción.
- [ ] **Bloque 7 — Pendiente:** Estimador de duración del torneo en el panel de administración según etapas, preguntas y tiempos configurados.
- [ ] **Bloque 8 — Pendiente:** Optimización final para alta concurrencia, caché con Redis, endurecimiento de seguridad (`trustProxies`, retiro de fallback en texto plano en login admin) y guía/preparación de despliegue en VPS.

---

## 3. Decisiones Ya Tomadas (Acuerdos de Diseño)

1. **Bloque 1 (Definición de "Cero" en Reinicio — Implementado):**
   - El reinicio **conserva siempre el Salón de la Fama (`historial_eventos`)**, además de `modulos`, `niveles` y `preguntas`.
   - "Cero" con `borrar_equipos = true` limpia `respuestas_equipos`, `entregas`, `integrantes` y `equipos`, y restablece `configuracion_eventos` (`estado_juego = 'registro'`, `fin_evento_at = null`, `pausado_at = null`, nueva `semilla_partida` garantizada distinta).
   - Con `borrar_equipos = false` conserva `equipos` e `integrantes` y resetea todos los contadores (`fase_actual = 1`, `preguntas_contestadas = 0`, `aciertos_totales = 0`, `puntaje_total = 0`, `tiempo_acumulado_ms = 0`, `en_desempate = false`, `finalizo_carrera = false`), `respuestas_equipos = 0` y `entregas = 0`.
2. **Bloque 2 (Sorteo Uniforme y Barajado Opaco de Opciones A/B/C/D — Implementado):**
   - El sorteo de preguntas usa `CRC32(SHA2(CONCAT(preguntas.id, "-", ?), 256))` con `semilla_partida`, asegurando la misma secuencia para todos los equipos y dispersión uniforme para preguntas nuevas.
   - Las 4 opciones (`opcion_a..d`) se permutan en el servidor antes de serializar el JSON usando `PERMUTACIONES_ABCD[hexdec(substr(hash('sha256', "{$equipoId}:{$preguntaId}:{$semilla}"), 0, 8)) % 24]`, sin enviar al cliente qué clave tenía originalmente cada opción en la BD. Al recibir `POST /api/enviar-respuesta`, el servidor traduce la letra visual elegida a la clave original y la guarda en `respuestas_equipos.opcion_elegida`.
3. **Bloque 3 (Banco de Preguntas):**
   - ~200 preguntas para las 3 etapas regulares:
     - **Fase 1:** muy fácil (propuesta inicial: 70 preguntas, incluyendo una pregunta amena).
     - **Fase 2:** dificultad media (propuesta inicial: 70 preguntas, incluyendo una pregunta amena).
     - **Fase 3:** muy difícil (propuesta inicial: 60 preguntas, incluyendo una pregunta amena).
   - ~30 preguntas adicionales de **Muerte Súbita** (dificilísimas).
   - Todas las preguntas deben ser **originales**, sin copiar de fuentes externas.
4. **Bloque 4 (Anti-Trampa de Pestaña y Bloqueos en Admin):**
   - **Concursantes:** se mantiene el bloqueo de copiar/pegar y disuasivos de captura; si el concursante sale de la pestaña o aplicación, recibe aviso y **no puede responder esa pregunta** (queda anulada/bloqueada en el servidor y debe esperar a la siguiente). Todo incidente queda registrado en el servidor y es visible desde `/admin`.
   - **Admin:** autoguardado con notificaciones *toast*. Por defecto la configuración solo es editable en estados `registro` y `finalizado`; mientras esté `en_curso`, `pausado` o `desempate`, se bloquean módulo activo, duración global, puntos/segundos por etapa y el banco de preguntas.
5. **Bloque 5 (Regla de Racha):**
   - La racha de aciertos consecutivos se rompe ante cualquiera de estos 3 eventos: **respuesta incorrecta**, **tiempo agotado** o **bloqueo por salir de la pestaña**.
6. **Bloque 8 (Infraestructura Final):**
   - Se abordará al final del proyecto; proveedor y dimensionamiento del VPS aún por definir.

---

## 4. Correcciones de Producción que NO Deben Revertirse

1. **`GET /api/carrera-en-vivo` sin caché de archivo ni sesión:** mantener sin `Cache::remember` en [`EspectadorController.php`](file:///home/vaxer/seventech-trivia/app/Http/Controllers/EspectadorController.php) y con `->withoutMiddleware('web')` en [`routes/web.php`](file:///home/vaxer/seventech-trivia/routes/web.php) hasta el Bloque 8 (Redis).
2. **`.htaccess` en la raíz del proyecto:** conservar [`.htaccess`](file:///home/vaxer/seventech-trivia/.htaccess) (protege `.env`, `.git`, `artisan`, `.sql`, `.zip` y redirige a `public/`).
3. **Rutas relativas en `/admin` y `trustProxies`:** mantener `action="/admin/..."` relativos en [`resources/views/admin/index.blade.php`](file:///home/vaxer/seventech-trivia/resources/views/admin/index.blade.php) y `$middleware->trustProxies(at: '*')` en [`bootstrap/app.php`](file:///home/vaxer/seventech-trivia/bootstrap/app.php).
4. **Robustez de `AdminController::login()`:** mantener limpieza de comillas (`trim(..., "'\"")`) y `try/catch` alrededor de `Hash::check`.
5. **Tolerancia del Polling HTTP:** mantener intervalos de `2000 ms`, `AbortController` de `8000 ms`, umbral de **6 fallos seguidos** en espectador y **5 fallos seguidos** en móvil, e ignorar abortos durante `beforeunload`.
6. **Reinicio atómico sin `TRUNCATE` y con `lockForUpdate()` (Bloque 1):** mantener `query()->delete()` y orden estricto de locks (`ConfiguracionEvento` → `Equipo`) en [`AdminController.php`](file:///home/vaxer/seventech-trivia/app/Http/Controllers/AdminController.php) y [`ConcursanteController.php`](file:///home/vaxer/seventech-trivia/app/Http/Controllers/ConcursanteController.php).
7. **Sorteo uniforme `CRC32(SHA2(...))` y barajado determinista opaco de opciones (Bloque 2):** mantener en [`ConcursanteController.php`](file:///home/vaxer/seventech-trivia/app/Http/Controllers/ConcursanteController.php) y [`AdminController.php`](file:///home/vaxer/seventech-trivia/app/Http/Controllers/AdminController.php).

---

## 5. Reglas Permanentes de Trabajo

- **Ramas:** `desarrollo` parte de `main`; cada bloque se trabaja en su propia rama `bloque-X-...` creada desde `desarrollo`. Commits pequeños y claros. Prohibido hacer `push` o `merge` a `main` sin instrucción explícita.
- **Tests seguros:** Ejecutar pruebas únicamente contra `seventech_trivia_test` (nunca contra `seventech_trivia`).
- **Protección de archivos y entorno:** Prohibido borrar archivos sin listarlos primero y obtener confirmación. Prohibido leer o modificar `.env` o tocar `composer.json`/`composer.lock` sin autorización previa.
- **Stack:** Mantener Laravel 13 + MySQL 8 + Blade + Tailwind CDN + JS Vanilla (sin React ni frameworks adicionales).

---

## 6. Riesgos Abiertos y Deuda Técnica Identificada

1. **Banco actual de 60 preguntas de prueba (`DatabaseSeeder.php`) — Se reemplaza en el Bloque 3:**
   - Aunque el barajado determinista en servidor del Bloque 2 ya neutraliza por completo el sesgo visual en el cliente (logrando 25% por cada posición `A/B/C/D`), el banco de prueba se sustituirá en el Bloque 3 por las ~230 preguntas originales definitivas (70/70/60 + 30 de muerte súbita) con distribución equilibrada desde el origen.
2. **Fallback en texto plano en `AdminController::login()` (revisar en Bloque 8):**
   - Actualmente el método `login()` permite `hash_equals` si `ADMIN_PASSWORD_HASH` no empieza con `$2`. En el Bloque 8 se evaluará exigir exclusivamente bcrypt válido.
3. **Rotación opcional de `APP_KEY` en cPanel:**
   - Si el servidor de producción usa la clave inicial de ejemplo, es recomendable rotarla antes del evento definitivo con `php artisan key:generate --force`.

---

## 7. Comandos de Verificación Rápida

```bash
# Ejecutar la suite completa de pruebas (contra seventech_trivia_test)
php artisan test

# Verificar que todas las rutas y controladores cargan sin errores
php artisan route:list

# Comprobar estado limpio del árbol de trabajo y rama actual
git status
```

---

## 8. Próximo Paso

- **Integrar `bloque-2-algoritmo-banco` en `desarrollo` e iniciar el Bloque 3 (`bloque-3-banco-preguntas`):** diseñar y poblar el nuevo banco de ~200 preguntas originales de carrera (70 muy fáciles / 70 medias / 60 muy difíciles, con una pregunta amena por fase) más ~30 preguntas dificilísimas de muerte súbita.
