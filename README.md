# SevenTech Trivia — Plataforma de Competencia en Vivo

Sistema web en tiempo real desarrollado para competencias presenciales de tecnología y programación (**Seven Tech Community / DATACLUB**). Diseñado para soportar **45 a 100+ equipos concurrentes** desde red Wi-Fi compartida en auditorio mediante arquitectura ligera basada en servidor dedicado / VPS.

---

## Arquitectura y Stack Tecnológico

- **Backend:** PHP `^8.3` + Laravel `^13.17`
- **Base de Datos:** MySQL 8 (índices de concurrencia e idempotencia transaccional)
- **Caché y Sesiones:** Redis (`SESSION_DRIVER=redis`, `CACHE_STORE=redis` en producción VPS)
- **Frontend:** Vistas Blade + Tailwind CSS (CDN) + JavaScript Vanilla + HTTP Short Polling tolerante a fallos
- **Estética y UX:** Lienzo `<canvas id="canvas-fondo-interactivo">` oscuro profundo con partículas optimizadas (~35 FPS, DPR limitado a `1.5`, soporte `prefers-reduced-motion: reduce`), bordes esmeralda animados, diálogos nativos `<dialog>` y notificaciones `popover="manual"`.
- **Infraestructura Objetivo:** VPS Dedicado Ubuntu 24.04 LTS + Nginx + PHP 8.3-FPM + MySQL 8 + Redis + HTTPS (Certbot). Ver guía completa en [`docs/DESPLIEGUE_VPS.md`](docs/DESPLIEGUE_VPS.md) y arquitectura detallada en [`docs/STACK.md`](docs/STACK.md).

---

## Funcionalidades Principales

1. **Tres Vistas Sincronizadas en Vivo:**
   - **`/` (Portal Principal):** Acceso a los 3 roles y Salón de la Fama con eventos históricos.
   - **`/jugar` (Concursante Móvil):** Registro de equipo (2 a 5 integrantes por defecto) o recuperación mediante PIN, reloj global sincronizado, barra de rachas, retroalimentación inmediata y cabecera responsive (`320px+`).
   - **`/espectador` (Pantalla Gigante):** Pista de carrera 2D en vivo, código QR dinámico con detección automática de IP LAN en entorno local y origen HTTPS real en producción, banner de eventos recientes en vivo, podio animado y confeti multi-ráfaga al finalizar.
   - **`/admin` (Panel de Control):** Autenticación protegida (`ADMIN_PASSWORD_HASH` bcrypt estricto en producción), control de estados (`registro`, `en_curso`, `pausado`, `desempate`, `finalizado`), calculadora inteligente de duración del torneo con semáforo en vivo, gestión de etapas/módulos/preguntas, importador masivo CSV/JSON y monitor de incidentes anti-trampa.

2. **Banco de Preguntas Multitemático (360 Preguntas Originales):**
   - `DatabaseSeeder` carga **10 módulos tecnológicos** y **360 preguntas verificadas** (110 Etapa 1, 110 Etapa 2, 100 Etapa 3 y 40 Muerte Súbita) con distribución exacta de **25% por opción** (`90 a`, `90 b`, `90 c`, `90 d`) y modo *"Todos los Módulos"* (`modulo_activo_id = null`) activo por defecto.
   - Cada etapa normal entrega exactamente **1 pregunta amena** (`es_amena = true`) en turno determinista y el resto técnicas, sin exponer `respuesta_correcta` ni `es_amena` al frontend.
   - Importador masivo `.csv` y `.json` en `/admin/preguntas/importar` con plantillas descargables (`/admin/preguntas/plantilla/{csv|json}`), validación transaccional y deduplicación automática.

3. **Justicia Algorítmica, Concurrencia y Escudo Anti-IA:**
   - **Sorteo uniforme e idéntico:** Ordenamiento pseudoaleatorio idéntico para todos los equipos usando `semilla_partida` con `CRC32(SHA2(CONCAT(preguntas.id, "-", ?), 256))`.
   - **Barajado opaco A/B/C/D:** En `GET /api/estado-juego`, el servidor permuta las 4 opciones sobre las 24 permutaciones de $S_4$ por `(equipo_id, pregunta_id, semilla_partida)` y traduce la letra elegida en `POST /api/enviar-respuesta` antes de calificar.
   - **Escudo Anti-IA en servidor:** Disuasivos de copia/captura en cliente y anulación automática en servidor (`POST /api/reportar-salida-pantalla`) al salir de la pestaña/ventana (`visibilitychange`, `blur`, `pagehide`), rompiendo la racha activa y registrando el incidente para auditoría en `/admin`.
   - **Tolerancia Wi-Fi NAT:** Limitadores de peticiones por token/sesión/equipo en `AppServiceProvider` + índices compuestos (`equipos_ranking_idx`, `preguntas_modulo_nivel_activa_amena_idx`).

---

## Instalación Local Rápida

```bash
# 1. Instalar dependencias PHP
composer install

# 2. Configurar entorno local (editar credenciales MySQL y ADMIN_PASSWORD_HASH / ADMIN_PASSWORD)
cp .env.example .env
php artisan key:generate

# 3. Ejecutar migraciones y sembrar el banco inicial (10 módulos + 360 preguntas)
php artisan migrate
php artisan db:seed

# 4. Levantar servidor accesible desde celulares en la misma red Wi-Fi
php artisan serve --host=0.0.0.0 --port=8000
```

> Al abrir `http://0.0.0.0:8000/espectador`, el sistema detectará automáticamente la IP LAN física para generar un QR escaneable desde dispositivos móviles en `/jugar`.

---

## Pruebas Automatizadas (PHPUnit)

La suite de pruebas está configurada en [`phpunit.xml`](phpunit.xml) para ejecutarse **exclusivamente contra la base de datos separada `seventech_trivia_test`** (protegida por `tests/TestCase.php` para evitar tocar la base principal).

```bash
# Crear base de pruebas la primera vez (si no existe):
# CREATE DATABASE seventech_trivia_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

php artisan test
```

---

## Despliegue en Producción

Consulta la guía paso a paso para servidor dedicado / VPS Ubuntu 24.04 (Nginx + PHP 8.3-FPM + MySQL 8 + Redis + SSL + alternativa mediante paquete `.zip` y volcado `.sql`) en [`docs/DESPLIEGUE_VPS.md`](docs/DESPLIEGUE_VPS.md).
