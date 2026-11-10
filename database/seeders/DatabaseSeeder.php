<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\ConfiguracionEvento;
use App\Models\Modulo;
use App\Models\Nivel;
use App\Models\Pregunta;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Usuario Administrador (sin contraseña fija: se toma de ADMIN_SEED_PASSWORD o se genera aleatoria)
        User::create([
            'name' => 'Admin Seven Tech',
            'email' => 'admin@seventech.com',
            'password' => Hash::make(env('ADMIN_SEED_PASSWORD') ?: Str::random(32)),
        ]);

        // 2. Módulos Escalables
        $moduloTech = Modulo::create([
            'nombre' => 'Comunidad Tecnológica & Cultura Geek',
            'descripcion' => 'Innovación, desarrollo, IA, redes, ciberseguridad y cultura digital',
            'icono' => 'cpu',
            'activo' => true,
        ]);

        Modulo::create([
            'nombre' => 'Desarrollo de Software & Arquitectura',
            'descripcion' => 'Lenguajes, bases de datos, estructuras y patrones de diseño',
            'icono' => 'code',
            'activo' => true,
        ]);

        Modulo::create([
            'nombre' => 'Infraestructura, Linux & Ciberseguridad',
            'descripcion' => 'Servidores, redes de datos, protocolos y seguridad informática',
            'icono' => 'shield',
            'activo' => true,
        ]);

        // 3. Configuración Inicial del Evento (15 min globales + Semilla aleatoria)
        ConfiguracionEvento::create([
            'nombre_evento'          => 'Seven Tech Trivia 2026',
            'max_integrantes_equipo' => 5,
            'max_equipos_permitidos' => 30,
            'tiempo_total_minutos'   => 15,
            'semilla_partida'        => Str::random(16),
            'estado_juego'           => 'registro',
            'modulo_activo_id'       => $moduloTech->id,
        ]);

        // 4. Niveles (5 preguntas sorteadas por fase de un banco sin límite)
        $nivel1 = Nivel::create([
            'nombre'             => 'Etapa 1: Calentamiento Geek',
            'orden_dificultad'   => 1,
            'cantidad_preguntas' => 5,
            'puntos_por_acierto' => 100,
            'tiempo_limite_seg'  => 25,
            'color_hex'          => '#10B981',
            'es_desempate'       => false,
        ]);

        $nivel2 = Nivel::create([
            'nombre'             => 'Etapa 2: Velocidad Crucero',
            'orden_dificultad'   => 2,
            'cantidad_preguntas' => 5,
            'puntos_por_acierto' => 200,
            'tiempo_limite_seg'  => 25,
            'color_hex'          => '#00E5FF',
            'es_desempate'       => false,
        ]);

        $nivel3 = Nivel::create([
            'nombre'             => 'Etapa 3: Recta Final Pro',
            'orden_dificultad'   => 3,
            'cantidad_preguntas' => 5,
            'puntos_por_acierto' => 300,
            'tiempo_limite_seg'  => 20,
            'color_hex'          => '#F59E0B',
            'es_desempate'       => false,
        ]);

        $nivelDesempate = Nivel::create([
            'nombre'             => 'Muerte Súbita: Desempate',
            'orden_dificultad'   => 4,
            'cantidad_preguntas' => 3,
            'puntos_por_acierto' => 500,
            'tiempo_limite_seg'  => 15,
            'color_hex'          => '#EF4444',
            'es_desempate'       => true,
        ]);

        // =========================================================================
        // BANCO ETAPA 1 (FÁCILES - 18 PREGUNTAS PARA SORTEAR 5)
        // =========================================================================
        $preguntasNivel1 = [
            ['enunciado' => '¿Qué plataforma es mundialmente conocida por alojar repositorios de código con Git y tiene a Octocat como mascota?', 'opcion_a' => 'GitLab', 'opcion_b' => 'Bitbucket', 'opcion_c' => 'GitHub', 'opcion_d' => 'SourceForge', 'respuesta_correcta' => 'c'],
            ['enunciado' => '¿Cómo se llama el pingüino que es la mascota oficial del kernel de Linux?', 'opcion_a' => 'Tux', 'opcion_b' => 'Gnu', 'opcion_c' => 'Konqi', 'opcion_d' => 'Daemon', 'respuesta_correcta' => 'a'],
            ['enunciado' => 'En el desarrollo web, ¿qué significan las siglas HTML?', 'opcion_a' => 'High Tech Modern Language', 'opcion_b' => 'HyperText Markup Language', 'opcion_c' => 'HyperTransfer Machine Language', 'opcion_d' => 'Home Tool Markup Language', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Qué día del año se celebra internacionalmente el "Día del Programador"?', 'opcion_a' => 'El día 100 del año', 'opcion_b' => 'El día 256 del año', 'opcion_c' => 'El día 314 del año', 'opcion_d' => 'El día 128 del año', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Cuál de los siguientes NO es un lenguaje de programación, sino un motor de bases de datos relacional?', 'opcion_a' => 'Python', 'opcion_b' => 'Kotlin', 'opcion_c' => 'PostgreSQL', 'opcion_d' => 'Rust', 'respuesta_correcta' => 'c'],
            ['enunciado' => '¿A cuántos Megabytes (MB) equivale exactamente 1 Gigabyte (GB) en el sistema binario tradicional (GiB)?', 'opcion_a' => '1000 MB', 'opcion_b' => '1024 MB', 'opcion_c' => '512 MB', 'opcion_d' => '2048 MB', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Qué empresa tecnológica desarrolló originalmente el sistema operativo Android antes de ser adquirida por Google?', 'opcion_a' => 'Android Inc.', 'opcion_b' => 'Nokia Labs', 'opcion_c' => 'Motorola Mobility', 'opcion_d' => 'Sun Microsystems', 'respuesta_correcta' => 'a'],
            ['enunciado' => '¿Para qué se utiliza el lenguaje CSS en el desarrollo web?', 'opcion_a' => 'Para gestionar bases de datos SQL', 'opcion_b' => 'Para definir el diseño visual y estilo de las páginas', 'opcion_c' => 'Para configurar servidores Linux', 'opcion_d' => 'Para compilar código en C++', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Qué combinación de teclas universal se usa para deshacer la última acción en la mayoría de editores de código?', 'opcion_a' => 'Ctrl + Y', 'opcion_b' => 'Ctrl + Z', 'opcion_c' => 'Ctrl + X', 'opcion_d' => 'Ctrl + D', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Qué significan las siglas "CPU" en el hardware de una computadora?', 'opcion_a' => 'Central Processing Unit', 'opcion_b' => 'Computer Personal Unit', 'opcion_c' => 'Core Programming Utility', 'opcion_d' => 'Central Power Unit', 'respuesta_correcta' => 'a'],
            ['enunciado' => '¿Cuál es el navegador web de código abierto que sirve como base para Google Chrome, Brave y Microsoft Edge?', 'opcion_a' => 'WebKit', 'opcion_b' => 'Gecko', 'opcion_c' => 'Chromium', 'opcion_d' => 'Netscape', 'respuesta_correcta' => 'c'],
            ['enunciado' => 'En programación, ¿cómo se le llama a un error o fallo en el código de un software?', 'opcion_a' => 'Patch', 'opcion_b' => 'Bug', 'opcion_c' => 'Feature', 'opcion_d' => 'Token', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Qué lenguaje de programación tiene como logotipo oficial dos serpientes entrelazadas?', 'opcion_a' => 'Ruby', 'opcion_b' => 'PHP', 'opcion_c' => 'Python', 'opcion_d' => 'Go', 'respuesta_correcta' => 'c'],
            ['enunciado' => '¿Qué tipo de memoria de la computadora es volátil, es decir, pierde sus datos cuando se apaga el equipo?', 'opcion_a' => 'Disco SSD NVMe', 'opcion_b' => 'Memoria RAM', 'opcion_c' => 'Memoria ROM', 'opcion_d' => 'Memoria Flash USB', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Qué formato de intercambio de datos ligero es el más utilizado hoy en día en las APIs web modernas?', 'opcion_a' => 'CSV', 'opcion_b' => 'JSON', 'opcion_c' => 'DOCX', 'opcion_d' => 'BMP', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Cómo se llama el editor de código gratuito más popular desarrollado por Microsoft con soporte para miles de extensiones?', 'opcion_a' => 'Sublime Text', 'opcion_b' => 'Eclipse', 'opcion_c' => 'Visual Studio Code', 'opcion_d' => 'NetBeans', 'respuesta_correcta' => 'c'],
            ['enunciado' => '¿Qué significan las siglas "URL" en internet?', 'opcion_a' => 'Universal Router Link', 'opcion_b' => 'Uniform Resource Locator', 'opcion_c' => 'UnifiedRealtime Language', 'opcion_d' => 'User Remote Login', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Cuál de estas tecnologías permite ejecutar contenedores ligeros para empaquetar aplicaciones con todas sus dependencias?', 'opcion_a' => 'Docker', 'opcion_b' => 'Photoshop', 'opcion_c' => 'WordPress', 'opcion_d' => 'Blender', 'respuesta_correcta' => 'a'],
        ];

        // =========================================================================
        // BANCO ETAPA 2 (INTERMEDIAS - 18 PREGUNTAS PARA SORTEAR 5)
        // =========================================================================
        $preguntasNivel2 = [
            ['enunciado' => '¿Quién creó originalmente tanto el kernel de Linux como el sistema de control de versiones Git?', 'opcion_a' => 'Richard Stallman', 'opcion_b' => 'Dennis Ritchie', 'opcion_c' => 'Linus Torvalds', 'opcion_d' => 'Ken Thompson', 'respuesta_correcta' => 'c'],
            ['enunciado' => 'En redes de datos, ¿qué puerto estándar utiliza por defecto el protocolo seguro HTTPS?', 'opcion_a' => 'Puerto 80', 'opcion_b' => 'Puerto 22', 'opcion_c' => 'Puerto 443', 'opcion_d' => 'Puerto 8080', 'respuesta_correcta' => 'c'],
            ['enunciado' => '¿Qué código de estado HTTP indica el error "Too Many Requests" cuando un usuario supera el límite de peticiones?', 'opcion_a' => '403 Forbidden', 'opcion_b' => '429 Too Many Requests', 'opcion_c' => '502 Bad Gateway', 'opcion_d' => '401 Unauthorized', 'respuesta_correcta' => 'b'],
            ['enunciado' => 'En Inteligencia Artificial, ¿qué significan las siglas "LLM" en modelos como Gemini, Claude o GPT?', 'opcion_a' => 'Linear Logic Machine', 'opcion_b' => 'Large Language Model', 'opcion_c' => 'Local Learning Module', 'opcion_d' => 'Logical Lexical Matrix', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Qué estructura de datos sigue estrictamente el principio LIFO (Last In, First Out)?', 'opcion_a' => 'Cola (Queue)', 'opcion_b' => 'Pila (Stack)', 'opcion_c' => 'Árbol Binario', 'opcion_d' => 'Grafo Dirigido', 'respuesta_correcta' => 'b'],
            ['enunciado' => 'En bases de datos SQL, ¿qué cláusula se utiliza para filtrar resultados DESPUÉS de aplicar un GROUP BY?', 'opcion_a' => 'WHERE', 'opcion_b' => 'HAVING', 'opcion_c' => 'ORDER BY', 'opcion_d' => 'DISTINCT', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Qué puerto estándar utiliza por defecto el protocolo SSH para administrar servidores Linux de forma remota?', 'opcion_a' => 'Puerto 21', 'opcion_b' => 'Puerto 22', 'opcion_c' => 'Puerto 25', 'opcion_d' => 'Puerto 3306', 'respuesta_correcta' => 'b'],
            ['enunciado' => 'En el patrón de arquitectura MVC (usado en Laravel), ¿qué componente se encarga de interactuar directamente con la base de datos?', 'opcion_a' => 'La Vista (View)', 'opcion_b' => 'El Modelo (Model)', 'opcion_c' => 'La Plantilla CSS', 'opcion_d' => 'El Router', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Qué comando de Git se utiliza para descargar los cambios del repositorio remoto y fusionarlos automáticamente con tu rama actual?', 'opcion_a' => 'git clone', 'opcion_b' => 'git pull', 'opcion_c' => 'git commit', 'opcion_d' => 'git init', 'respuesta_correcta' => 'b'],
            ['enunciado' => 'En redes IPv4, ¿a qué dirección IP apunta siempre el nombre de host "localhost" (loopback)?', 'opcion_a' => '192.168.1.1', 'opcion_b' => '127.0.0.1', 'opcion_c' => '0.0.0.0', 'opcion_d' => '255.255.255.0', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Qué protocolo de red se encarga de traducir nombres de dominio (como google.com) a direcciones IP numéricas?', 'opcion_a' => 'DHCP', 'opcion_b' => 'DNS', 'opcion_c' => 'FTP', 'opcion_d' => 'SMTP', 'respuesta_correcta' => 'b'],
            ['enunciado' => 'En Linux, ¿qué significa el permiso numérico "777" al ejecutar el comando chmod?', 'opcion_a' => 'Solo lectura para el propietario', 'opcion_b' => 'Lectura, escritura y ejecución para todos los usuarios', 'opcion_c' => 'Archivo oculto del sistema', 'opcion_d' => 'Bloqueo total del archivo', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Cuál es el gestor de paquetes oficial para instalar librerías y dependencias en proyectos PHP como Laravel?', 'opcion_a' => 'npm', 'opcion_b' => 'Composer', 'opcion_c' => 'pip', 'opcion_d' => 'Maven', 'respuesta_correcta' => 'b'],
            ['enunciado' => 'En programación orientada a objetos (POO), ¿cómo se llama la capacidad de una clase de heredar atributos y métodos de otra?', 'opcion_a' => 'Polimorfismo', 'opcion_b' => 'Herencia', 'opcion_c' => 'Abstracción', 'opcion_d' => 'Compilación', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Qué método del protocolo HTTP es el estándar para enviar datos sensibles (como un formulario de registro) al servidor?', 'opcion_a' => 'GET', 'opcion_b' => 'POST', 'opcion_c' => 'HEAD', 'opcion_d' => 'OPTIONS', 'respuesta_correcta' => 'b'],
            ['enunciado' => 'En una base de datos relacional, ¿qué restricción garantiza que una columna no pueda tener valores duplicados?', 'opcion_a' => 'DEFAULT', 'opcion_b' => 'UNIQUE', 'opcion_c' => 'NULLABLE', 'opcion_d' => 'INDEX', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Qué framework multiplataforma creado por Google utiliza el lenguaje Dart para compilar aplicaciones móviles y web?', 'opcion_a' => 'React Native', 'opcion_b' => 'Flutter', 'opcion_c' => 'Ionic', 'opcion_d' => 'Xamarin', 'respuesta_correcta' => 'b'],
            ['enunciado' => 'En ciberseguridad, ¿cómo se llama el ataque que intenta engañar al usuario haciéndose pasar por una entidad legítima para robar sus claves?', 'opcion_a' => 'Defacement', 'opcion_b' => 'Phishing', 'opcion_c' => 'Overclocking', 'opcion_d' => 'Refactoring', 'respuesta_correcta' => 'b'],
        ];

        // =========================================================================
        // BANCO ETAPA 3 (DIFÍCILES - 18 PREGUNTAS PARA SORTEAR 5)
        // =========================================================================
        $preguntasNivel3 = [
            ['enunciado' => '¿Cómo se titula el paper científico de 2017 que introdujo la arquitectura "Transformer", base de la IA generativa moderna?', 'opcion_a' => 'Computing Machinery and Intelligence', 'opcion_b' => 'Attention Is All You Need', 'opcion_c' => 'Deep Residual Learning', 'opcion_d' => 'Generative Adversarial Nets', 'respuesta_correcta' => 'b'],
            ['enunciado' => 'En desarrollo backend, ¿cuál es el método más efectivo a nivel de código para prevenir ataques de Inyección SQL (SQLi)?', 'opcion_a' => 'Ocultar errores con CSS', 'opcion_b' => 'Usar consultas preparadas (Prepared Statements) y parametrización', 'opcion_c' => 'Cambiar el puerto 3306 de MySQL', 'opcion_d' => 'Codificar la URL en Base64', 'respuesta_correcta' => 'b'],
            ['enunciado' => 'Dentro del modelo OSI de redes, ¿en qué capa operan principalmente los enrutadores (Routers) y el direccionamiento lógico IP?', 'opcion_a' => 'Capa 2 (Enlace de datos)', 'opcion_b' => 'Capa 3 (Red)', 'opcion_c' => 'Capa 4 (Transporte)', 'opcion_d' => 'Capa 7 (Aplicación)', 'respuesta_correcta' => 'b'],
            ['enunciado' => 'En sistemas distribuidos, el Teorema CAP establece que un sistema solo puede garantizar simultáneamente 2 de 3 propiedades. ¿Cuáles son?', 'opcion_a' => 'Concurrencia, Autenticación y Persistencia', 'opcion_b' => 'Consistencia, Disponibilidad y Tolerancia al particionado', 'opcion_c' => 'Capacidad, Ancho de banda y Procesamiento', 'opcion_d' => 'Cifrado, Auditoría y Privacidad', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Cuál es la complejidad temporal en notación Big-O de una Búsqueda Binaria (Binary Search) en un arreglo ordenado?', 'opcion_a' => 'O(n)', 'opcion_b' => 'O(n log n)', 'opcion_c' => 'O(log n)', 'opcion_d' => 'O(n²)', 'respuesta_correcta' => 'c'],
            ['enunciado' => 'En criptografía asimétrica, si Alicia quiere enviarle un mensaje confidencial a Bob, ¿con qué clave debe cifrar el mensaje?', 'opcion_a' => 'Con la clave privada de Alicia', 'opcion_b' => 'Con la clave pública de Bob', 'opcion_c' => 'Con la clave privada de Bob', 'opcion_d' => 'Con un hash MD5', 'respuesta_correcta' => 'b'],
            ['enunciado' => 'En arquitecturas de Inteligencia Artificial, ¿qué significan las siglas RAG al conectar un LLM con documentos propios?', 'opcion_a' => 'Random Access Generation', 'opcion_b' => 'Retrieval-Augmented Generation', 'opcion_c' => 'Recursive Algorithm Graph', 'opcion_d' => 'Realtime Automated Gateway', 'respuesta_correcta' => 'b'],
            ['enunciado' => 'En bases de datos transaccionales, ¿qué significan las siglas ACID?', 'opcion_a' => 'Atomicity, Consistency, Isolation, Durability', 'opcion_b' => 'Access, Control, Integrity, Data', 'opcion_c' => 'Array, Cache, Index, Database', 'opcion_d' => 'Async, Concurrent, Isolated, Distributed', 'respuesta_correcta' => 'a'],
            ['enunciado' => 'En los principios SOLID de ingeniería de software, ¿qué representa la letra "S"?', 'opcion_a' => 'Static Typing Principle', 'opcion_b' => 'Single Responsibility Principle (Responsabilidad Única)', 'opcion_c' => 'Secure Socket Principle', 'opcion_d' => 'Synchronized Object Principle', 'respuesta_correcta' => 'b'],
            ['enunciado' => 'En redes TCP/IP, ¿cuál es la secuencia correcta de paquetes del "Three-Way Handshake" para establecer una conexión?', 'opcion_a' => 'ACK -> SYN -> FIN', 'opcion_b' => 'SYN -> SYN-ACK -> ACK', 'opcion_c' => 'PING -> PONG -> ACK', 'opcion_d' => 'GET -> POST -> 200 OK', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Qué vulnerabilidad web ocurre cuando una aplicación inserta datos no sanitizados del usuario en el DOM ejecutando scripts maliciosos en el navegador?', 'opcion_a' => 'Cross-Site Scripting (XSS)', 'opcion_b' => 'DDoS de capa 4', 'opcion_c' => 'ARP Spoofing', 'opcion_d' => 'Buffer Overflow de BIOS', 'respuesta_correcta' => 'a'],
            ['enunciado' => 'En una subred IPv4 con máscara /24 (255.255.255.0), ¿cuántas direcciones IP utilizables para hosts existen?', 'opcion_a' => '256 hosts', 'opcion_b' => '254 hosts', 'opcion_c' => '128 hosts', 'opcion_d' => '512 hosts', 'respuesta_correcta' => 'b'],
            ['enunciado' => 'En sistemas operativos, ¿cómo se denomina la situación en la que dos o más procesos quedan bloqueados mutuamente esperando recursos que tiene el otro?', 'opcion_a' => 'Overclocking', 'opcion_b' => 'Deadlock (Interbloqueo)', 'opcion_c' => 'Paging', 'opcion_d' => 'Multithreading', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Cuál es el algoritmo de consenso clásico utilizado por redes como Bitcoin para validar bloques mediante poder de cómputo?', 'opcion_a' => 'Proof of Authority', 'opcion_b' => 'Proof of Work (PoW)', 'opcion_c' => 'Round Robin', 'opcion_d' => 'Dijkstra', 'respuesta_correcta' => 'b'],
            ['enunciado' => 'En algoritmos de grafos, ¿qué algoritmo se utiliza clásicamente para encontrar el camino más corto desde un nodo origen con pesos no negativos?', 'opcion_a' => 'Algoritmo de Bubble Sort', 'opcion_b' => 'Algoritmo de Dijkstra', 'opcion_c' => 'Algoritmo de QuickSort', 'opcion_d' => 'Algoritmo César', 'respuesta_correcta' => 'b'],
            ['enunciado' => 'En seguridad web (Laravel/Bcrypt), ¿qué es un "Salt" al almacenar contraseñas con funciones hash?', 'opcion_a' => 'Una cadena aleatoria añadida antes de hashear para evitar ataques de Rainbow Tables', 'opcion_b' => 'Un cortafuegos de red', 'opcion_c' => 'Un certificado SSL caducado', 'opcion_d' => 'Una copia de seguridad en texto plano', 'respuesta_correcta' => 'a'],
            ['enunciado' => 'En el estándar JSON Web Token (JWT), ¿cuáles son las tres partes separadas por puntos que componen el token?', 'opcion_a' => 'Model, View, Controller', 'opcion_b' => 'Header, Payload, Signature', 'opcion_c' => 'Public, Private, Secret', 'opcion_d' => 'Host, Port, Path', 'respuesta_correcta' => 'b'],
            ['enunciado' => 'En bases de datos vectoriales e IA, ¿qué métrica matemática se usa habitualmente para medir la similitud semántica entre dos embeddings?', 'opcion_a' => 'Desviación estándar simple', 'opcion_b' => 'Similitud del Coseno (Cosine Similarity)', 'opcion_c' => 'Regla de Ruffini', 'opcion_d' => 'Módulo aritmético', 'respuesta_correcta' => 'b'],
        ];

        // =========================================================================
        // BANCO MUERTE SÚBITA / DESEMPATE (6 PREGUNTAS PARA SORTEAR 3)
        // =========================================================================
        $preguntasDesempate = [
            ['enunciado' => '¿En qué año fue lanzada oficialmente la primera versión del kernel de Linux (v0.01) por Linus Torvalds?', 'opcion_a' => '1989', 'opcion_b' => '1991', 'opcion_c' => '1995', 'opcion_d' => '1985', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Cuántos bits componen exactamente una dirección IPv6?', 'opcion_a' => '32 bits', 'opcion_b' => '64 bits', 'opcion_c' => '128 bits', 'opcion_d' => '256 bits', 'respuesta_correcta' => 'c'],
            ['enunciado' => '¿Quién es considerada históricamente la primera programadora por su algoritmo para la máquina analítica de Charles Babbage?', 'opcion_a' => 'Grace Hopper', 'opcion_b' => 'Ada Lovelace', 'opcion_c' => 'Margaret Hamilton', 'opcion_d' => 'Hedy Lamarr', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿Qué científico de la computación dirigió el equipo que diseñó el protocolo TCP/IP junto con Bob Kahn y es considerado uno de los "Padres de Internet"?', 'opcion_a' => 'Vint Cerf', 'opcion_b' => 'Alan Turing', 'opcion_c' => 'John von Neumann', 'opcion_d' => 'Bill Gates', 'respuesta_correcta' => 'a'],
            ['enunciado' => '¿Quién lideró la División de Ingeniería de Software del MIT que desarrolló el código de navegación a bordo para la misión Apolo 11?', 'opcion_a' => 'Radia Perlman', 'opcion_b' => 'Margaret Hamilton', 'opcion_c' => 'Carol Shaw', 'opcion_d' => 'Katherine Johnson', 'respuesta_correcta' => 'b'],
            ['enunciado' => '¿En qué laboratorio trabajó Tim Berners-Lee cuando inventó la World Wide Web (WWW) en 1989?', 'opcion_a' => 'Bell Labs', 'opcion_b' => 'CERN (Organización Europea para la Investigación Nuclear)', 'opcion_c' => 'Xerox PARC', 'opcion_d' => 'MIT Media Lab', 'respuesta_correcta' => 'b'],
        ];

        foreach ($preguntasNivel1 as $p) {
            Pregunta::create(array_merge($p, ['modulo_id' => $moduloTech->id, 'nivel_id' => $nivel1->id]));
        }
        foreach ($preguntasNivel2 as $p) {
            Pregunta::create(array_merge($p, ['modulo_id' => $moduloTech->id, 'nivel_id' => $nivel2->id]));
        }
        foreach ($preguntasNivel3 as $p) {
            Pregunta::create(array_merge($p, ['modulo_id' => $moduloTech->id, 'nivel_id' => $nivel3->id]));
        }
        foreach ($preguntasDesempate as $p) {
            Pregunta::create(array_merge($p, ['modulo_id' => $moduloTech->id, 'nivel_id' => $nivelDesempate->id]));
        }
    }
}