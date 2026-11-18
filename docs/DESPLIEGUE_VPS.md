# Guía de Despliegue en Producción — VPS Dedicado (Ubuntu 24.04)

Esta guía sustituye definitivamente al antiguo flujo en hosting compartido / cPanel. **SevenTech Trivia** se despliega en un **servidor dedicado o VPS Ubuntu 24.04 LTS** con **Nginx + PHP 8.3-FPM + MySQL 8 + Redis + SSL (Certbot)** para soportar **45 a 100+ equipos concurrentes** en el auditorio.

---

## 0. Checklist de Datos a Solicitar Antes del Despliegue

Antes de iniciar la configuración en producción, recopila con el proveedor de infraestructura o dueño del servidor:

1. **Dominio / Subdominio y DNS:** Nombre final (ej. `trivia.tudominio.com`) y confirmación de registro `A` / `AAAA` apuntando a la IP pública del VPS (y si pasa por Cloudflare o proxy CDN, sus rangos IP para `TRUSTED_PROXIES`).
2. **Acceso al Servidor:** IP pública, usuario SSH con privilegios `sudo` (o clave privada `.pem`) o método alternativo de entrega si no hay SSH/Git (ver Sección 9).
3. **Sistema y Servicios:** Confirmación de Ubuntu 24.04 LTS (o similar), puertos `80` y `443` abiertos en firewall/grupo de seguridad para tráfico web y emisión SSL Let's Encrypt.
4. **Credenciales y Parámetros Privados:**
   - Contraseña nueva para el usuario MySQL dedicado (`seventech_user`).
   - Contraseña elegida para el Administrador del torneo (para generar su hash bcrypt `ADMIN_PASSWORD_HASH` en producción).
   - Confirmación de uso de Redis local (`127.0.0.1:6379`) o endpoint/contraseña si es gestionado.

---

## 1. Preparación Base del VPS (Ubuntu 24.04 LTS)

Conéctate por SSH como usuario con privilegios `sudo` y actualiza paquetes:

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server redis-server git unzip curl certbot python3-certbot-nginx
sudo apt install -y php8.3-fpm php8.3-mysql php8.3-redis php8.3-mbstring php8.3-xml php8.3-curl php8.3-bcmath php8.3-zip php8.3-intl
```

Verifica versiones instaladas:

```bash
php -v       # PHP 8.3.x
mysql --version
redis-cli ping   # Debe responder PONG
```

---

## 2. Configuración de MySQL 8 y Redis

### 2.1. Base de datos dedicada (`seventech_trivia`)
```bash
sudo mysql
```

Dentro de la consola SQL:

```sql
CREATE DATABASE seventech_trivia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'seventech_user'@'127.0.0.1' IDENTIFIED BY 'CAMBIA_ESTA_CLAVE_SEGURA';
GRANT ALL PRIVILEGES ON seventech_trivia.* TO 'seventech_user'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;
```

### 2.2. Blindaje básico de Redis (`/etc/redis/redis.conf`)
Confirma que Redis escucha únicamente en loopback (`bind 127.0.0.1 -::1`) para que no quede expuesto a internet:

```bash
sudo grep -E '^bind ' /etc/redis/redis.conf
sudo systemctl enable --now redis-server
```

---

## 3. Ajuste de PHP 8.3-FPM para 45–100+ Equipos Simultáneos

Con polling cada 2 segundos desde celulares y pantalla gigante, ajusta los workers de PHP-FPM en `/etc/php/8.3/fpm/pool.d/www.conf`:

```ini
pm = dynamic
pm.max_children = 40
pm.start_servers = 8
pm.min_spare_servers = 6
pm.max_spare_servers = 16
pm.max_requests = 500
```

Asegura también `opcache.enable=1` y `opcache.validate_timestamps=0` (solo en producción estable; recuerda reiniciar PHP-FPM tras desplegar cambios de código) en `/etc/php/8.3/fpm/php.ini`, y reinicia PHP-FPM:

```bash
sudo systemctl restart php8.3-fpm
```

---

## 4. Clonado, Dependencias, `.env` y Hash Admin Obligatorio

```bash
sudo mkdir -p /var/www/seventech-trivia
sudo chown -R $USER:www-data /var/www/seventech-trivia
cd /var/www/seventech-trivia

git clone <URL_DEL_REPOSITORIO> .
git checkout main

composer install --no-dev --optimize-autoloader
cp .env.example .env
```

Genera la clave de aplicación y el **hash bcrypt obligatorio** para `/admin` (en producción `AdminController::login()` **rechaza cualquier clave en texto plano**):

```bash
php artisan key:generate
php artisan tinker --execute="echo Hash::make('TuClaveAdminSuperSegura');"
```

Edita `.env` con tus valores reales:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://trivia.tudominio.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=seventech_trivia
DB_USERNAME=seventech_user
DB_PASSWORD="CAMBIA_ESTA_CLAVE_SEGURA"

SESSION_DRIVER=redis
SESSION_LIFETIME=240
SESSION_SECURE_COOKIE=true

CACHE_STORE=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

TRUSTED_PROXIES=127.0.0.1
ADMIN_PASSWORD_HASH='$2y$12$PEGAR_AQUI_EL_HASH_CON_COMILLAS_SIMPLES'
```

> [!IMPORTANT]
> Mantén `ADMIN_PASSWORD_HASH` siempre entre **comillas simples (`'...'`)** para evitar que el shell o Dotenv interpreten los signos `$` del hash bcrypt. Si usas Cloudflare delante de Nginx, añade sus IPs en `TRUSTED_PROXIES`.

---

## 5. Permisos, Migraciones, Sembrado Inicial y Caché de Laravel

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

php artisan migrate --force
php artisan db:seed --force   # Carga 10 módulos + 400 preguntas originales (solo en instalación nueva)
php artisan optimize
```

> [!WARNING]
> No ejecutes `db:seed` en una BD que ya tenga historial o preguntas personalizadas si no deseas resetear o duplicar el catálogo inicial.

---

## 6. Nginx + HTTPS con Certbot

Crea `/etc/nginx/sites-available/seventech-trivia`:

```nginx
server {
    listen 80;
    server_name trivia.tudominio.com;
    root /var/www/seventech-trivia/public;

    index index.php;
    charset utf-8;
    client_max_body_size 10M;

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 30s;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Activa el sitio, verifica sintaxis y emite certificado SSL gratuito con Certbot:

```bash
sudo ln -s /etc/nginx/sites-available/seventech-trivia /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

sudo certbot --nginx -d trivia.tudominio.com
```

---

## 7. Actualizaciones Posteriores sin Downtime Lento

Cada vez que subas una nueva versión al VPS:

```bash
cd /var/www/seventech-trivia
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
sudo systemctl reload php8.3-fpm
```

---

## 8. Checklist Previo al Torneo en Auditorio

1. Verificar que `https://trivia.tudominio.com/up` devuelve HTTP `200`.
2. Entrar a `https://trivia.tudominio.com/espectador` y comprobar que el código QR apunta a `https://trivia.tudominio.com/jugar`.
3. Escanear el QR con un teléfono conectado al Wi-Fi del auditorio y validar registro de equipo con 2 integrantes.
4. Entrar a `/admin` verificando la clave bcrypt y comprobar que la **Calculadora del Torneo** muestra semáforo en **🟢 Duración óptima**.
5. Confirmar que `redis-cli info clients` y `tail -n 20 storage/logs/laravel.log` están limpios antes de abrir el registro oficial.

---

## 9. Despliegue Alternativo sin Git/SSH Directo (Paquete `.zip` + Volcado `.sql`)

Si el proveedor no habilita `git clone` directo en el servidor o solicita un paquete cerrado para cargar por panel/SFTP:

### 9.1. Empaquetado limpio del código (sin `.env`, caché local ni `.git`)

Desde la raíz del proyecto local (después de `composer install --no-dev --optimize-autoloader` en un directorio temporal o excluyendo desarrollo):

```bash
zip -r seventech-trivia-release.zip . \
  -x ".git/*" ".env" "node_modules/*" "storage/logs/*.log" \
     "storage/framework/cache/data/*" "storage/framework/sessions/*" \
     "storage/framework/views/*.php" "tests/*"
```

> Si subes sin ejecutar Composer en el servidor, asegúrate de incluir `vendor/` generado con `composer install --no-dev --optimize-autoloader` para **PHP 8.3** y limpiar caches antes (`php artisan optimize:clear`).

### 9.2. Generación de volcado inicial `.sql` (solo estructura + banco de 400 preguntas)

Si el administrador de BD requiere importar un `.sql` inicial en lugar de correr `php artisan migrate && php artisan db:seed`:

```bash
# Desde entorno local ya migrado y sembrado (sin datos sensibles ni historial de prueba)
mysqldump -u TU_USUARIO -p --single-transaction --routines --triggers \
  seventech_trivia > seventech_trivia_inicial.sql
```

> [!WARNING]
> Verifica que la base exportada no contenga equipos ni contraseñas personales y recuerda generar el `.env` de producción en el servidor con un `APP_KEY` nuevo (`php artisan key:generate`) y un `ADMIN_PASSWORD_HASH` propio antes de publicar.


