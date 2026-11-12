# Stack Tecnológico y Arquitectura — SevenTech Trivia

Este documento explica, paso a paso y en lenguaje claro para estudiantes de desarrollo de software, qué tecnologías utiliza **SevenTech Trivia**, por qué se eligió cada una y cómo trabajan juntas durante una competencia en vivo en un auditorio.

---

## 1. Resumen de Versiones del Proyecto

Las versiones exactas declaradas e instaladas en el proyecto (leídas de [`composer.json`](file:///home/vaxer/seventech-trivia/composer.json), [`composer.lock`](file:///home/vaxer/seventech-trivia/composer.lock), [`package.json`](file:///home/vaxer/seventech-trivia/package.json) y [`config/`](file:///home/vaxer/seventech-trivia/config/app.php)) son:

| Capa | Tecnología | Versión en el proyecto | Archivo de referencia |
| :--- | :--- | :--- | :--- |
| **Lenguaje Backend** | PHP | `^8.3` (ejecutando en `8.5.4`) | `composer.json` |
| **Framework Backend** | Laravel Framework | `^13.17` (instalada `v13.38.0`) | `composer.json` / `composer.lock` |
| **Consola interactiva** | Laravel Tinker | `^3.0` | `composer.json` |
| **Base de Datos** | MySQL / MariaDB | Compatible con MySQL `8.0+` / MariaDB `10.6+` | `config/database.php` |
| **Estilos CSS (en vistas)** | Tailwind CSS (vía CDN) + CSS Cyberpunk propio + Notyf (toasts CDN) | CDN + `@tailwindcss/vite ^4.0.0` en `package.json` | `resources/views/partials/cyberpunk-head.blade.php` / `admin/index.blade.php` |
| **Bundler de desarrollo** | Vite + `laravel-vite-plugin` | `^8.0.0` / `^3.1` | `package.json` |
| **Generador de QR** | `qrcodejs` | `1.0.0` (CDN CloudFlare) | `resources/views/espectador/index.blade.php` |
| **Efectos de podio** | `canvas-confetti` | `1.6.0` (CDN jsDelivr) | `resources/views/espectador/index.blade.php` |
| **Pruebas automatizadas** | PHPUnit | `^12.5.12` | `composer.json` |

---

## 2. Backend (Servidor y Lógica del Juego)

### PHP (`^8.3`) y Laravel (`v13.38.0`)
El backend es el "árbitro" de la trivia. Está escrito en **PHP** sobre el framework **Laravel** siguiendo el patrón **MVC (Modelo–Vista–Controlador)**:

- **Rutas ([`routes/web.php`](file:///home/vaxer/seventech-trivia/routes/web.php))**: Define todas las direcciones URL de la aplicación y las divide en 3 roles:
  1. **Concursante (`/jugar` y `/api/*`)**: para los equipos que responden desde el celular.
  2. **Espectador (`/espectador` y `/api/carrera-en-vivo`)**: para la pantalla gigante del auditorio.
  3. **Administrador (`/admin/*`)**: para el operador que controla estados, preguntas, módulos y desempates.
- **Controladores ([`app/Http/Controllers/`](file:///home/vaxer/seventech-trivia/app/Http/Controllers))**:
  - [`ConcursanteController.php`](file:///home/vaxer/seventech-trivia/app/Http/Controllers/ConcursanteController.php): registra equipos, entrega la pregunta asignada y califica las respuestas calculando el puntaje y el tiempo en el servidor.
  - [`EspectadorController.php`](file:///home/vaxer/seventech-trivia/app/Http/Controllers/EspectadorController.php): calcula el avance porcentual de cada robot en la pista 2D (85% por puntaje + 15% por preguntas contestadas) y el orden de la tabla de posiciones.
  - [`AdminController.php`](file:///home/vaxer/seventech-trivia/app/Http/Controllers/AdminController.php): gestiona el ciclo de vida del torneo (`registro`, `en_curso`, `pausado`, `desempate`, `finalizado`), el banco de preguntas y el Salón de la Fama.
- **Modelos Eloquent ([`app/Models/`](file:///home/vaxer/seventech-trivia/app/Models))**: representan las tablas de la base de datos como objetos PHP (`ConfiguracionEvento`, `Modulo`, `Nivel`, `Pregunta`, `Equipo`, `Integrante`, `Entrega`, `RespuestaEquipo`, `HistorialEvento`).

---

## 3. Base de Datos (MySQL)

Toda la información persistente vive en **MySQL** (definida en [`database/migrations/`](file:///home/vaxer/seventech-trivia/database/migrations)):

1. `configuracion_eventos`: guarda una única fila con el estado actual del torneo, el módulo activo (`null` por defecto para el modo multitemático *"🌐 Todos los Módulos"*), la duración global en minutos, la marca de tiempo `fin_evento_at`, `pausado_at` y la `semilla_partida`.
2. `modulos` y `niveles`: permiten escalar las **10 categorías tecnológicas** y las 4 etapas (Etapa 1, Etapa 2, Etapa 3 y Muerte Súbita) con distintos puntos y segundos límite por pregunta.
3. `preguntas`: banco exhaustivo de **360 preguntas originales** con 4 opciones (`a`, `b`, `c`, `d`), su `respuesta_correcta` (balanceada exactamente al 25%: 90 por letra) y la bandera booleana `es_amena` (45 preguntas amenas/curiosas de cultura geek para garantizar exactamente 1 pregunta amena por etapa normal y el resto técnicas). Soporta importación masiva CSV/JSON con deduplicación desde `/admin`.
4. `equipos` e `integrantes`: datos del equipo, su `token_sesion` de 60 caracteres, su `pin_recuperacion` de 4 dígitos, contador de incidentes `salidas_pantalla` y contadores acumulados (`puntaje_total`, `aciertos_totales`, `preguntas_contestadas`, `tiempo_acumulado_ms`).
5. `entregas`: registra el instante exacto con precisión de milisegundos (`TIMESTAMP(3)`) en que el servidor entregó por primera vez cada pregunta a cada equipo.
6. `respuestas_equipos`: historial de cada respuesta enviada (o anulada por salida de pestaña con `bloqueada_por_salida = true`), con restricción única `UNIQUE(equipo_id, pregunta_id)` para que sea físicamente imposible puntuar o penalizar dos veces la misma pregunta.
7. `historial_eventos`: guarda en columnas `JSON` el podio Top 3 y la tabla final de ediciones pasadas.

> **¿Por qué requiere MySQL específicamente?**  
> Para sortear las preguntas de un banco grande manteniendo exactamente el mismo orden pseudoaleatorio para todos los equipos en una partida y sin sesgos por ID, el servidor ordena con la función SQL `CRC32(SHA2(CONCAT(preguntas.id, "-", ?), 256))` usando `semilla_partida`.

---

## 4. Frontend (Vistas Blade, Tailwind CSS y JavaScript Vanilla)

El proyecto no necesita un framework pesado de SPA (como React o Angular) porque busca cargar al instante incluso en celulares de gama media conectados a un Wi-Fi saturado:

- **Laravel Blade ([`resources/views/`](file:///home/vaxer/seventech-trivia/resources/views))**: renderiza la estructura HTML inicial e inyecta el token CSRF (`meta[name="csrf-token"]`).
- **Tailwind CSS + CSS Cyberpunk ([`resources/views/partials/cyberpunk-head.blade.php`](file:///home/vaxer/seventech-trivia/resources/views/partials/cyberpunk-head.blade.php))**: aplica el diseño futurista con bordes de neón, fuentes `Orbitron` y `Rajdhani` y animaciones CSS suaves (`transition: left 0.8s cubic-bezier(...)`) para mover los avatares de los robots en la pista.
- **JavaScript Vanilla (`fetch` + `AbortController` + DOM API)**:
  - Actualiza la interfaz sin recargar la página.
  - Inserta textos dinámicos (como las opciones de las preguntas y los nombres de los equipos) usando `.textContent` en lugar de `.innerHTML` para evitar que opciones con etiquetas como `<div>` o `<script>` rompan la pantalla o introduzcan vulnerabilidades XSS.

---

## 5. ¿Cómo funciona el "Tiempo Real"? (HTTP Short Polling vs. WebSockets)

En lugar de usar **WebSockets** (que requieren mantener decenas de conexiones TCP abiertas permanentemente y un servidor adicional como Reverb, Pusher o Soketi), el proyecto utiliza **HTTP Short Polling inteligente con `setTimeout` encadenado**:

1. **En el celular del concursante (`/jugar`)**:
   - Una vez que termina una petición a `GET /api/estado-juego`, espera **2 segundos** (`setTimeout`) antes de lanzar la siguiente.
   - Cada petición tiene un tiempo máximo de espera de **8 segundos** (`AbortController`). Si la red del auditorio se pone lenta, **no se acumulan peticiones encimadas**, aplica *backoff* ante fallos de red y solo muestra el banner de reconexión tras **5 fallos consecutivos** (~15 s), ignorando abortos intencionales al recargar o navegar (`beforeunload`).
2. **En la pantalla gigante (`/espectador`)**:
   - Consulta `GET /api/carrera-en-vivo` esperando **2 segundos** tras completar la petición anterior, con timeout de **8 segundos** (`AbortController`), moviendo suavemente los robots con transiciones CSS de 700–800 ms.
   - El banner rojo de reconexión solo se activa si ocurren **6 fallos consecutivos**, e ignora abortos intencionales durante `beforeunload`.
   - Además, la ruta `GET /api/carrera-en-vivo` se sirve con `->withoutMiddleware('web')` en [`routes/web.php`](file:///home/vaxer/seventech-trivia/routes/web.php) (endpoint JSON público sin lectura/escritura de sesión ni bloqueo de archivo de sesión).

**Ventaja para despliegues académicos o en hosting compartido:** funciona en cualquier servidor PHP estándar (Apache/Nginx/cPanel) sin demonios en segundo plano ni puertos extra.

---

## 6. Sesiones, Consulta Directa sin Caché de Archivo y Rate Limiting en Wi-Fi Compartido

- **Sesiones (`config/session.php`)**:
  - Cuando un equipo se registra o recupera su cuenta con su PIN de 4 dígitos, el servidor regenera el ID de sesión (`$request->session()->regenerate()`) y guarda `equipo_token` en la sesión del servidor. El navegador solo guarda la cookie encriptada `laravel_session`.
  - Así, **ningún endpoint recibe `equipo_id` desde el celular**: el servidor identifica al equipo exclusivamente por su sesión segura.
- **Telemetría sin bloqueo de sesión ni caché de archivo (`EspectadorController`)**:
  - `GET /api/carrera-en-vivo` consulta MySQL directamente (< 1 ms para ≤ 30 equipos) y prescinde tanto del middleware `web` (`->withoutMiddleware('web')`) como de `Cache::remember` sobre sistema de archivos. Esto evita bloqueos de archivo (*file locks*) y respuestas vacías o errores 500 intermitentes en discos compartidos de cPanel (la caché en memoria con Redis se evaluará en el Bloque 8 para VPS).
- **Rate Limiting por Equipo/Sesión ([`AppServiceProvider.php`](file:///home/vaxer/seventech-trivia/app/Providers/AppServiceProvider.php))**:
  - En un auditorio, todos los celulares salen a internet con la **misma dirección IP pública** del router Wi-Fi (NAT). Si el límite fuera por IP, un solo equipo bloquearía a todo el auditorio.
  - Por eso los limitadores (`juego-estado`, `juego-respuesta`, `registro`) crean cubetas **por token de equipo o ID de sesión**, dejando un techo global muy alto por IP (3000–6000 req/min).
  - Para el PIN de recuperación (`throttle:pin`), el límite es de **5 intentos por minuto y 20 por hora por cada nombre de equipo objetivo**, impidiendo adivinar el PIN de 4 dígitos por fuerza bruta.

---

## 7. Capas de Protección Anti-Trampa

1. **Ocultación de la respuesta correcta y barajado determinista en el servidor**: el modelo [`Pregunta.php`](file:///home/vaxer/seventech-trivia/app/Models/Pregunta.php) declara `protected $hidden = ['respuesta_correcta', 'es_amena']`. Además, `ConcursanteController` aplica una permutación determinista de las 4 opciones (`A`, `B`, `C`, `D`) única por `(equipo_id, pregunta_id, semilla_partida)` sobre las 24 permutaciones de $S_4$ y traduce la letra visual elegida de vuelta en el servidor al calificar. Así, el navegador jamás recibe cuál es la columna original en la BD ni la respuesta correcta, y dos equipos vecinos ven las opciones en distinto orden (distribución uniforme ~25% por letra).
2. **Validación de pregunta asignada (T1)**: `POST /api/enviar-respuesta` recalcula en el servidor qué pregunta le toca exactamente al equipo (`asignacionActual`). Si alguien intenta enviar por consola otro `pregunta_id`, el servidor responde `409 Conflict` sin sumar puntos.
3. **Justicia deportiva con semilla por partida**: todos los equipos reciben las mismas preguntas en el mismo orden pseudoaleatorio determinado por `semilla_partida` (oculta al público con `makeHidden('semilla_partida')`). Al reiniciar la partida, se genera una nueva semilla aleatoria con `Str::random(16)`.
4. **Cálculo de tiempo 100% en el servidor (T2)**: cuando el servidor entrega una pregunta por primera vez, guarda `entregada_at` con milisegundos en la tabla `entregas` (`insertOrIgnore`). Recargar la página no reinicia ese reloj. Al recibir la respuesta, el servidor resta `now() - entregada_at` e ignora cualquier tiempo que mande el cliente. Si excede el límite de la etapa (+1.5 s de margen por latencia de red), se califica como fuera de tiempo (`0 puntos`).
5. **Candado atómico contra doble envío (T5)**: la calificación y la suma de puntos se ejecutan dentro de `DB::transaction()` respaldadas por el índice único `UNIQUE(equipo_id, pregunta_id)` en `respuestas_equipos`.
6. **Penalización en servidor al salir de pestaña y disuasivos en cliente**: además del bloqueo de clic derecho/copiar/pegar y la cortina "Escudo Anti-IA" (`visibilitychange`), salir de la pantalla con una pregunta activa (excluyendo recargas `beforeunload`) reporta a `POST /api/reportar-salida-pantalla`, anulando la pregunta en la BD con `0` puntos (`bloqueada_por_salida = true`) e incrementando `equipos.salidas_pantalla` visible en `/admin`.

