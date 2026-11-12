# Guía de Despliegue a Producción — SevenTech Trivia

Esta guía detalla los requisitos técnicos reales deducidos del código fuente de **SevenTech Trivia**, qué servicios adicionales **no** son necesarios, las variables de entorno para producción y el paso a paso tanto para **cPanel / Hosting Compartido** como para un **VPS Ubuntu con Nginx**.

---

## 1. Requisitos del Servidor (Deducidos del Proyecto)

### PHP y Extensiones
Según [`composer.json`](file:///home/vaxer/seventech-trivia/composer.json) y [`composer.lock`](file:///home/vaxer/seventech-trivia/composer.lock) (`laravel/framework v13.38.0`):
- **Versión mínima de PHP:** `PHP 8.3` o superior (`8.3`, `8.4` u `8.5`).
- **Extensiones de PHP requeridas:**
  - `ctype`
  - `curl`
  - `dom` / `xml` / `libxml`
  - `fileinfo`
  - `filter`
  - `hash`
  - `mbstring` (usada intensivamente en `mb_strtolower(..., 'UTF-8')` para normalizar nombres de equipos e integrantes)
  - `openssl` (cifrado de cookies y sesiones)
  - `pcre`
  - `pdo` y `pdo_mysql` (conexión a MySQL)
  - `session`
  - `tokenizer`

### Base de Datos
- **MySQL `8.0+` o MariaDB `10.6+`**.
- **Importante:** No usar SQLite en producción. El proyecto utiliza la función nativa de MySQL/MariaDB `CRC32(CONCAT(id, ?))` en [`ConcursanteController.php`](file:///home/vaxer/seventech-trivia/app/Http/Controllers/ConcursanteController.php) para sortear las preguntas con el mismo orden pseudoaleatorio para todos los equipos, además de tipos `JSON` (`historial_eventos`) y `TIMESTAMP(3)` con milisegundos (`entregas`).

### ¿Hace falta Node.js o `public/build` en el servidor?
- **NO hace falta Node.js ni compilar `public/build` en el servidor.**
- Revisando todas las vistas activas del sistema ([`inicio.blade.php`](file:///home/vaxer/seventech-trivia/resources/views/inicio.blade.php), [`concursante/index.blade.php`](file:///home/vaxer/seventech-trivia/resources/views/concursante/index.blade.php), [`espectador/index.blade.php`](file:///home/vaxer/seventech-trivia/resources/views/espectador/index.blade.php), [`admin/index.blade.php`](file:///home/vaxer/seventech-trivia/resources/views/admin/index.blade.php) y [`partials/cyberpunk-head.blade.php`](file:///home/vaxer/seventech-trivia/resources/views/partials/cyberpunk-head.blade.php)), ninguna utiliza la directiva `@vite`. Todas cargan sus estilos y utilidades mediante `<script src="https://cdn.tailwindcss.com"></script>`, Google Fonts y CDNs (`qrcodejs` y `canvas-confetti`).
- *(Nota: como se usan CDNs para fuentes, Tailwind, QR y confeti, los dispositivos y la pantalla del auditorio deben tener salida a Internet).*

---

## 2. Servicios que NO Hacen Falta (Verificado en el Código)

Se inspeccionaron `app/`, `routes/web.php`, `routes/console.php` y `config/`:

| Servicio / Comando | ¿Lo usa el proyecto? | Explicación técnica |
| :--- | :---: | :--- |
| **Colas (`queue:work` / Supervisor)** | **NO** | No existen clases en `app/Jobs`, ni llamadas a `dispatch()` o `ShouldQueue`. Todo se procesa en la petición HTTP. |
| **Cron / Scheduler (`schedule:run`)** | **NO** | [`routes/console.php`](file:///home/vaxer/seventech-trivia/routes/console.php) solo tiene el comando de ejemplo `inspire`. El cierre del reloj global del torneo es perezoso (*lazy evaluation*) dentro de `obtenerEstadoYPregunta` y `enviarRespuesta`. |
| **Enlace simbólico (`php artisan storage:link`)** | **NO** | El sistema no sube archivos ni imágenes de usuarios (`Storage::disk('public')` no se usa). El logo y el ícono del robot viven directamente en `public/favicon.svg`. |
| **Servidor de WebSockets (Reverb / Pusher)** | **NO** | El tiempo real funciona mediante HTTP Short Polling (`fetch` cada 2 s tanto en espectador como en celulares, con timeout de 8 s). |

---

## 3. Variables de `.env` que Debes Definir en Producción

Tomando como base [`.env.example`](file:///home/vaxer/seventech-trivia/.env.example) y [`config/app.php`](file:///home/vaxer/seventech-trivia/config/app.php), define estos valores en tu archivo `.env` de producción (sin exponer secretos reales aquí):

```dotenv
APP_NAME="Seven Tech Trivia"
APP_ENV=production
APP_KEY=base64:GENERAR_CON_PHP_ARTISAN_KEY_GENERATE
APP_DEBUG=false
APP_URL=https://tu-dominio.com

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_de_tu_bd
DB_USERNAME=usuario_de_tu_bd
DB_PASSWORD="password_seguro_de_tu_bd"

# En un solo servidor web (VPS o cPanel), 'file' evita hacer un UPDATE en MySQL cada 2s por cada celular.
# Si tienes Redis disponible en VPS, puedes usar 'redis'.
SESSION_DRIVER=file
SESSION_LIFETIME=240
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true

CACHE_STORE=file
QUEUE_CONNECTION=sync

# Hash bcrypt de la contraseña del panel /admin.
# Genéralo localmente con: php artisan tinker --execute="echo Hash::make('TuClaveSecreta');"
# IMPORTANTE: usar comillas simples para que los signos '$' del hash bcrypt no se interpreten como variables.
ADMIN_PASSWORD_HASH='$2y$12$REEMPLAZAR_POR_TU_HASH_BCRYPT'
```

> **¿Por qué `SESSION_DRIVER=file` y `CACHE_STORE=file` (o `redis`) en vez de `database`?**  
> Durante el evento, 30 celulares consultan `/api/estado-juego` cada 2 segundos y el proyector consulta `/api/carrera-en-vivo` cada 2 segundos (este último sin middleware de sesión `web` ni caché de archivo para evitar bloqueos de E/S en cPanel). Con `file` (o `redis`), el estado de sesión de los concursantes y los contadores del *rate limiter* no saturan las tablas de MySQL con escrituras constantes.
>
> **Formato de `ADMIN_PASSWORD_HASH`:** En `.env` debe ir siempre entre **comillas simples** (`ADMIN_PASSWORD_HASH='$2y$12$...'`) para evitar que Dotenv expanda los signos `$` del hash bcrypt como variables vacías. Además, `AdminController::login()` limpia comillas residuales y captura cualquier `RuntimeException` del hasher para evitar errores 500 si el hash estuviera mal formateado.

---

## 4. Opción A: Guía Paso a Paso para cPanel / Hosting Compartido

### Paso 1: Verificar PHP en cPanel
1. Entra a **MultiPHP Manager** (Administrador MultiPHP) en cPanel.
2. Selecciona tu dominio o subdominio (ej. `trivia.tudominio.com`) y asigna **PHP 8.3** o **PHP 8.4**.
3. En **Select PHP Version / Extensions**, verifica que estén activas `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` y `ctype`.

### Paso 2: Crear la Base de Datos e Importar el `.sql`
1. En cPanel, abre **MySQL Database Wizard**:
   - Crea la base de datos (ej. `usuario_trivia`).
   - Crea el usuario de base de datos con una contraseña fuerte.
   - Asígnale **ALL PRIVILEGES** (Todos los privilegios) sobre esa base.
2. Si ya tienes un respaldo `.sql` exportado de tu base lista:
   - Abre **phpMyAdmin** en cPanel, selecciona la base `usuario_trivia`, pestaña **Importar**, sube tu archivo `.sql` y ejecuta.
   - *(Asegúrate de que tu `.sql` ya incluya la tabla `entregas` y la columna `pausado_at` de `configuracion_eventos`; si no, ejecuta las migraciones con `php artisan migrate --force` por terminal SSH).*

### Paso 3: Subir el Código y Apuntar el Dominio a `public/`
En local, antes de comprimir tu proyecto, instala las dependencias de producción:
```bash
composer install --no-dev --optimize-autoloader
```
Comprime el proyecto en un `.zip` (incluyendo `vendor/`, pero **sin** subir tu `.env` local ni `node_modules/`).

- **Caso ideal (Subdominio o Dominio Adicional en cPanel):**
  1. Sube y descomprime el proyecto en `/home/tu_usuario/seventech-trivia`.
  2. En cPanel → **Domains** (Dominios), edita el **Document Root** (Raíz del documento) de tu dominio/subdominio para que apunte directamente a:
     ```text
     /home/tu_usuario/seventech-trivia/public
     ```
- **Si el dominio o subdominio apunta a la raíz del proyecto en cPanel (sin cambiar el Document Root ni usar symlink):**
  1. El repositorio incluye un archivo [`.htaccess`](file:///home/vaxer/seventech-trivia/.htaccess) en la raíz que:
     - Bloquea con `403 Forbidden` cualquier acceso directo a `.env`, `.git`, `composer.json`, `composer.lock`, `package.json`, `phpunit.xml`, `artisan` y archivos `.sql`/`.zip`/`.md`/`.log`.
     - Redirige de forma transparente todas las peticiones hacia `public/`.
- **Si es el dominio principal y prefieres usar un enlace simbólico en `public_html`:**
  1. Sube todo el proyecto a `/home/tu_usuario/seventech-trivia`.
  2. Si tienes acceso SSH, reemplaza `public_html` por un enlace simbólico:
     ```bash
     rm -rf ~/public_html
     ln -s ~/seventech-trivia/public ~/public_html
     ```

### Notas Críticas de Proxy Inverso / HTTPS en cPanel
- **Confianza en proxies (`trustProxies`):** [`bootstrap/app.php`](file:///home/vaxer/seventech-trivia/bootstrap/app.php) configura `$middleware->trustProxies(at: '*')` para que Laravel reconozca el esquema HTTPS detrás del proxy SSL de cPanel/Apache.
- **Rutas relativas en formularios (`/admin`):** Todos los formularios del panel de administración ([`resources/views/admin/index.blade.php`](file:///home/vaxer/seventech-trivia/resources/views/admin/index.blade.php)) usan rutas relativas (`/admin/login`, `/admin/configuracion`, etc.) en lugar de `route()` absolutas, evitando que una redirección `http -> https` (`301/302`) convierta peticiones `POST` en `GET` y provoque el error `405 Method Not Allowed`.

### Paso 4: Configurar `.env`, Permisos y Caché
1. En el Administrador de Archivos de cPanel (en `/home/tu_usuario/seventech-trivia`), copia `.env.example` a `.env` y edítalo con los valores de la **Sección 3**.
2. Asegúrate de que las carpetas `storage/` y `bootstrap/cache/` tengan permisos de escritura (`775` o `755` recursivo).
3. Desde la **Terminal** de cPanel (o SSH), ejecuta:
   ```bash
   cd ~/seventech-trivia
   php artisan migrate --force
   php artisan optimize
   ```

### Paso 5: Activar HTTPS
1. En cPanel → **SSL/TLS Status** → ejecuta **Run AutoSSL** para emitir el certificado gratuito Let's Encrypt.
2. Verifica que `APP_URL=https://tu-dominio.com` use `https://`.

---

## 5. Opción B: Guía Paso a Paso para un VPS Ubuntu (22.04 / 24.04) con Nginx

### Paso 1: Instalar Nginx, MySQL 8 y PHP 8.3+
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server unzip git curl software-properties-common

# Si Ubuntu 22.04 no trae PHP 8.3 por defecto, agrega el PPA de Ondřej Surý:
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

sudo apt install -y php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml \
    php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl composer
```

### Paso 2: Crear la Base de Datos e Importar el `.sql` (o Migrar)
```bash
sudo mysql
```
Dentro del prompt de MySQL:
```sql
CREATE DATABASE seventech_trivia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'seventech_user'@'localhost' IDENTIFIED BY 'TU_PASSWORD_DB_FUERTE';
GRANT ALL PRIVILEGES ON seventech_trivia.* TO 'seventech_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```
Si vas a importar un archivo `dump.sql`:
```bash
mysql -u seventech_user -p seventech_trivia < /ruta/a/tu/dump.sql
```

### Paso 3: Clonar el Proyecto, Configurar `.env` y Permisos
```bash
sudo mkdir -p /var/www/seventech-trivia
sudo chown -R $USER:$USER /var/www/seventech-trivia
git clone <URL_DE_TU_REPOSITORIO> /var/www/seventech-trivia
cd /var/www/seventech-trivia

composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```
Edita `/var/www/seventech-trivia/.env` con `nano .env` aplicando las variables de la **Sección 3** (incluyendo `ADMIN_PASSWORD_HASH`).

Ejecuta migraciones (y el seeder solo si no importaste ya un `.sql` con datos):
```bash
php artisan migrate --force
# Solo si la base está vacía y quieres cargar el banco inicial de preguntas:
# php artisan db:seed --force

php artisan optimize
```
Asigna propietario y permisos a `storage` y `bootstrap/cache` para el usuario `www-data` de Nginx/PHP-FPM:
```bash
sudo chown -R www-data:www-data /var/www/seventech-trivia/storage /var/www/seventech-trivia/bootstrap/cache
sudo chmod -R ug+rwx /var/www/seventech-trivia/storage /var/www/seventech-trivia/bootstrap/cache
```

### Paso 4: Configurar Nginx Apuntando a `public/`
Crea el archivo `/etc/nginx/sites-available/seventech-trivia`:
```nginx
server {
    listen 80;
    server_name trivia.tudominio.com;
    root /var/www/seventech-trivia/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```
Activa el sitio y recarga Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/seventech-trivia /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Paso 5: Activar HTTPS con Certbot (Let's Encrypt)
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d trivia.tudominio.com
```

---

## 6. Cómo Probar el Despliegue Antes del Evento

1. **Portal y Espectador:**
   - Abre `https://tu-dominio.com/espectador` en la pantalla gigante.
   - Verifica que el código QR se dibuja correctamente y que el texto junto al QR muestra `https://tu-dominio.com/jugar` (nunca `localhost`).
   - En las DevTools del navegador (`F12` → Red), inspecciona `GET /api/carrera-en-vivo`: debe responder `200 OK` cada ~2 s y el JSON `evento` **no** debe incluir `semilla_partida`.
2. **Panel de Administración (`/admin`):**
   - Abre `https://tu-dominio.com/admin`.
   - Comprueba que la sección **"6. Zona de Pruebas y Simulador"** **no aparece** (porque `APP_ENV=production`).
   - Inicia sesión con tu contraseña real (cuyo hash configuraste en `ADMIN_PASSWORD_HASH`) y deja el estado en **Registro**.
3. **Flujo Completo con 2 Celulares Reales en el Wi-Fi del Auditorio:**
   - Escanea el QR con 2 teléfonos distintos y registra `Equipo Prueba 1` y `Equipo Prueba 2`.
   - Verifica que ambos aparecen en la línea de salida de `/espectador`.
   - Desde `/admin`, cambia el estado a **En Curso** y guarda.
   - Comprueba que **ambos celulares reciben exactamente la misma primera pregunta**, que al responder avanzan los robots en la pantalla gigante, y que al final puedes presionar **Reiniciar** con `borrar_equipos` marcado para dejar el sistema en cero listo para el público.

