# Estado del Proyecto — SevenTech Trivia

## 1. Estado Actual

- **Rama activa:** `bloque-0-limpieza` (creada desde `desarrollo`, la cual incorpora el commit `e1736d3` con las correcciones en producción sobre `main` `b000730`).
- **Entorno en producción (cPanel):** `https://trivia-seventech.softecsa.com` operativo.
- **Suite de pruebas:** **12 tests pasando (118 aserciones)** contra `seventech_trivia_test`:
  - `Tests\Unit\ExampleTest` (1 test)
  - `Tests\Feature\ExampleTest` (1 test)
  - `Tests\Feature\Bloque1SeguridadTest` (10 tests: ocultación de respuesta correcta, rechazo de pregunta no asignada `409`, mismo orden por `semilla_partida`, tiempo medido en servidor, idempotencia `UNIQUE(equipo_id, pregunta_id)`, sesión por cookie sin `equipo_id` en request, límite 2–5 integrantes, `rate limiting` del PIN `429`, protección de `/admin/*` y regeneración de `semilla_partida`).

---

## 2. Mapa y Checklist de Bloques (0 a 8)

- [x] **Bloque 0 (`bloque-0-limpieza`) — Completado:** Inventario y limpieza de archivos temporales/no usados, actualización de `.gitignore`, registro de correcciones de producción en `desarrollo`, alineación de [`docs/STACK.md`](file:///home/vaxer/seventech-trivia/docs/STACK.md) y [`docs/DESPLIEGUE.md`](file:///home/vaxer/seventech-trivia/docs/DESPLIEGUE.md), reglas permanentes en [`GEMINI.md`](file:///home/vaxer/seventech-trivia/GEMINI.md) y creación de este [`docs/ESTADO.md`](file:///home/vaxer/seventech-trivia/docs/ESTADO.md).
- [ ] **Bloque 1 — Pendiente:** Auditoría y corrección del reinicio a "Cero" (`AdminController::reiniciarJuego()`), garantizando atomicidad y conservando intacto el Salón de la Fama (`historial_eventos`).
- [ ] **Bloque 2 — Pendiente:** Auditoría del pool y ordenamiento del banco de preguntas por fase y barajado determinista de las opciones A/B/C/D con semilla estable.
- [ ] **Bloque 3 — Pendiente:** Reemplazo de las 60 preguntas de prueba por el banco definitivo original (~200 preguntas de carrera repartidas 70/70/60 en fases 1/2/3 + ~30 de muerte súbita dificilísima).
- [ ] **Bloque 4 — Pendiente:** (a) Concursantes: detección y bloqueo en servidor al salir de la pestaña/app (pierde esa pregunta y espera a la siguiente) con registro de incidentes visible en `/admin`. (b) Admin: autoguardado con toasts y bloqueo de edición de parámetros críticos cuando el evento está `en_curso`, `pausado` o `desempate`.
- [ ] **Bloque 5 — Pendiente:** Sistema de rachas en servidor y UI (la racha se rompe con respuesta incorrecta, tiempo agotado o bloqueo por salir de la pestaña).
- [ ] **Bloque 6 — Pendiente:** QR dinámico, experiencia móvil y validación de conexión en red local y producción.
- [ ] **Bloque 7 — Pendiente:** Estimador de duración del torneo en el panel de administración según etapas, preguntas y tiempos configurados.
- [ ] **Bloque 8 — Pendiente:** Optimización final para alta concurrencia, caché con Redis, endurecimiento de seguridad (`trustProxies`, retiro de fallback en texto plano en login admin) y guía/preparación de despliegue en VPS.

---

## 3. Decisiones Ya Tomadas (Acuerdos de Diseño)

1. **Bloque 1 (Definición de "Cero" en Reinicio):**
   - El reinicio **conserva siempre el Salón de la Fama (`historial_eventos`)**.
   - "Cero" significa limpiar/reiniciar: equipos, integrantes, respuestas (`respuestas_equipos`), entregas (`entregas`), rachas y restablecer la configuración de la partida (`estado = 'registro'`, `fin_evento_at = null`, `pausado_at = null`, nueva `semilla_partida`).
2. **Bloque 3 (Banco de Preguntas):**
   - ~200 preguntas para las 3 etapas regulares:
     - **Fase 1:** muy fácil (propuesta inicial: 70 preguntas, incluyendo una pregunta amena).
     - **Fase 2:** dificultad media (propuesta inicial: 70 preguntas, incluyendo una pregunta amena).
     - **Fase 3:** muy difícil (propuesta inicial: 60 preguntas, incluyendo una pregunta amena).
   - ~30 preguntas adicionales de **Muerte Súbita** (dificilísimas).
   - Todas las preguntas deben ser **originales**, sin copiar de fuentes externas.
3. **Bloque 4 (Anti-Trampa de Pestaña y Bloqueos en Admin):**
   - **Concursantes:** se mantiene el bloqueo de copiar/pegar y disuasivos de captura; si el concursante sale de la pestaña o aplicación, recibe aviso y **no puede responder esa pregunta** (queda anulada/bloqueada en el servidor y debe esperar a la siguiente). Todo incidente queda registrado en el servidor y es visible desde `/admin`.
   - **Admin:** autoguardado con notificaciones *toast*. Por defecto la configuración solo es editable en estados `registro` y `finalizado`; mientras esté `en_curso`, `pausado` o `desempate`, se bloquean módulo activo, duración global, puntos/segundos por etapa y el banco de preguntas.
4. **Bloque 5 (Regla de Racha):**
   - La racha de aciertos consecutivos se rompe ante cualquiera de estos 3 eventos: **respuesta incorrecta**, **tiempo agotado** o **bloqueo por salir de la pestaña**.
5. **Bloque 8 (Infraestructura Final):**
   - Se abordará al final del proyecto; proveedor y dimensionamiento del VPS aún por definir.

---

## 4. Correcciones de Producción que NO Deben Revertirse

1. **`GET /api/carrera-en-vivo` sin caché de archivo ni sesión:** mantener sin `Cache::remember` en [`EspectadorController.php`](file:///home/vaxer/seventech-trivia/app/Http/Controllers/EspectadorController.php) y con `->withoutMiddleware('web')` en [`routes/web.php`](file:///home/vaxer/seventech-trivia/routes/web.php) hasta el Bloque 8 (Redis).
2. **`.htaccess` en la raíz del proyecto:** conservar [`.htaccess`](file:///home/vaxer/seventech-trivia/.htaccess) (protege `.env`, `.git`, `artisan`, `.sql`, `.zip` y redirige a `public/`).
3. **Rutas relativas en `/admin` y `trustProxies`:** mantener `action="/admin/..."` relativos en [`resources/views/admin/index.blade.php`](file:///home/vaxer/seventech-trivia/resources/views/admin/index.blade.php) y `$middleware->trustProxies(at: '*')` en [`bootstrap/app.php`](file:///home/vaxer/seventech-trivia/bootstrap/app.php).
4. **Robustez de `AdminController::login()`:** mantener limpieza de comillas (`trim(..., "'\"")`) y `try/catch (\RuntimeException)` alrededor de `Hash::check`.
5. **Tolerancia del Polling HTTP:** mantener intervalos de `2000 ms`, `AbortController` de `8000 ms`, umbral de **6 fallos seguidos** en espectador y **5 fallos seguidos** en móvil, e ignorar abortos durante `beforeunload`.
6. **`Entrega::truncate()` en `AdminController::reiniciarJuego()`:** no tocado en el Bloque 0; se analizará y corregirá en el Bloque 1.

---

## 5. Reglas Permanentes de Trabajo

- **Ramas:** `desarrollo` parte de `main`; cada bloque se trabaja en su propia rama `bloque-X-...` creada desde `desarrollo`. Commits pequeños y claros. Prohibido hacer `push` o `merge` a `main` sin instrucción explícita.
- **Tests seguros:** Ejecutar pruebas únicamente contra `seventech_trivia_test` (nunca contra `seventech_trivia`).
- **Protección de archivos y entorno:** Prohibido borrar archivos sin listarlos primero y obtener confirmación. Prohibido leer o modificar `.env` o tocar `composer.json`/`composer.lock` sin autorización previa.
- **Stack:** Mantener Laravel 13 + MySQL 8 + Blade + Tailwind CDN + JS Vanilla (sin React ni frameworks adicionales).

---

## 6. Riesgos Abiertos y Deuda Técnica Identificada

1. **Hipótesis para investigar en el Bloque 1 (Reinicio del Juego):**
   - **(a) Commit implícito por DDL (`TRUNCATE`):** `Entrega::truncate()` ejecuta `TRUNCATE TABLE entregas` en MySQL, lo cual provoca un *implicit commit* en el motor y rompe la atomicidad de cualquier `DB::transaction()` en `AdminController::reiniciarJuego()`.
   - **(b) Condición de carrera (*race condition*) por peticiones en vuelo:** mientras el administrador ejecuta el reinicio, celulares con peticiones en vuelo a `/api/estado-juego` o `/api/enviar-respuesta` podrían insertar entregas o respuestas residuales.
   - **(c) Cobertura incompleta del estado "Cero":** verificar que el reinicio limpie absolutamente todas las tablas y columnas asociadas a una partida (equipos, integrantes, respuestas, entregas, rachas futuras y metadatos de configuración) sin tocar `historial_eventos`.
2. **Fallback en texto plano en `AdminController::login()` (revisar en Bloque 8):**
   - Actualmente el bloque `catch (\RuntimeException)` incluye un fallback `hash_equals($hashConfigurado, $passwordIngresado)` por si en cPanel se colocó texto plano en vez de bcrypt. En el Bloque 8 se evaluará exigir exclusivamente bcrypt válido.
3. **Rotación opcional de `APP_KEY` en cPanel:**
   - El commit inicial `b000730` tuvo un `APP_KEY` de ejemplo en `.env.example` (ya vaciado en `desarrollo`). Si el servidor de producción usa esa misma clave, es recomendable rotarla antes del evento definitivo con `php artisan key:generate --force`.

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

- **Iniciar el Bloque 1 (`bloque-1-reinicio`):** auditar `AdminController::reiniciarJuego()` y sus interacciones con `ConcursanteController`, contrastar las 3 hipótesis (commit implícito de `TRUNCATE`, peticiones en vuelo y cobertura total del "Cero" preservando `historial_eventos`), presentar el plan breve y escribir los tests de regresión correspondientes.
