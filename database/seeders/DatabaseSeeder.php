<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\ConfiguracionEvento;
use App\Models\Modulo;
use App\Models\Nivel;
use App\Models\Pregunta;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear Usuario Administrador para controlar el evento
        User::create([
            'name' => 'Admin Seven Tech',
            'email' => 'admin@seventech.com',
            'password' => Hash::make('seventech2026'),
        ]);

        // 2. Crear Módulos Escalables
        $moduloTech = Modulo::create([
            'nombre' => 'Comunidad Tecnológica & Cultura Geek',
            'descripcion' => 'Historia tech, innovación, IA, software libre y cultura digital',
            'icono' => 'cpu',
            'activo' => true,
        ]);

        Modulo::create([
            'nombre' => 'Desarrollo de Software & Web',
            'descripcion' => 'Lenguajes de programación, bases de datos y arquitectura',
            'icono' => 'code',
            'activo' => true,
        ]);

        Modulo::create([
            'nombre' => 'Redes, Linux & Ciberseguridad',
            'descripcion' => 'Protocolos, servidores, seguridad informática y sistemas operativos',
            'icono' => 'shield',
            'activo' => true,
        ]);

        // 3. Configuración Inicial del Evento
        ConfiguracionEvento::create([
            'nombre_evento' => 'Seven Tech Trivia 2026',
            'max_integrantes_equipo' => 5,
            'max_equipos_permitidos' => 30,
            'estado_juego' => 'registro',
            'modulo_activo_id' => $moduloTech->id,
        ]);

        // 4. Crear los Niveles (Etapas de la Carrera + Desempate)
        $nivel1 = Nivel::create([
            'nombre' => 'Etapa 1: Calentamiento Geek',
            'orden_dificultad' => 1,
            'cantidad_preguntas' => 5,
            'puntos_por_acierto' => 100,
            'tiempo_limite_seg' => 25,
            'color_hex' => '#10B981', // Verde Neón
            'es_desempate' => false,
        ]);

        $nivel2 = Nivel::create([
            'nombre' => 'Etapa 2: Velocidad Crucero',
            'orden_dificultad' => 2,
            'cantidad_preguntas' => 5,
            'puntos_por_acierto' => 200,
            'tiempo_limite_seg' => 25,
            'color_hex' => '#00E5FF', // Cian Eléctrico Seven Tech
            'es_desempate' => false,
        ]);

        $nivel3 = Nivel::create([
            'nombre' => 'Etapa 3: Recta Final Pro',
            'orden_dificultad' => 3,
            'cantidad_preguntas' => 5,
            'puntos_por_acierto' => 300,
            'tiempo_limite_seg' => 20,
            'color_hex' => '#F59E0B', // Ámbar
            'es_desempate' => false,
        ]);

        $nivelDesempate = Nivel::create([
            'nombre' => 'Muerte Súbita: Desempate',
            'orden_dificultad' => 4,
            'cantidad_preguntas' => 3,
            'puntos_por_acierto' => 500,
            'tiempo_limite_seg' => 15,
            'color_hex' => '#EF4444', // Rojo Laser
            'es_desempate' => true,
        ]);

        // 5. Banco de Preguntas: ETAPA 1 (Fáciles - 5 preguntas)
        $preguntasNivel1 = [
            [
                'enunciado' => '¿Qué plataforma es mundialmente conocida por alojar repositorios de código usando Git y tiene un gato-pulpo (Octocat) como mascota?',
                'opcion_a' => 'GitLab',
                'opcion_b' => 'Bitbucket',
                'opcion_c' => 'GitHub',
                'opcion_d' => 'SourceForge',
                'respuesta_correcta' => 'c',
            ],
            [
                'enunciado' => '¿Cómo se llama la mascota oficial del kernel de Linux?',
                'opcion_a' => 'Tux',
                'opcion_b' => 'Gnu',
                'opcion_c' => 'konqi',
                'opcion_d' => 'Daemon',
                'respuesta_correcta' => 'a',
            ],
            [
                'enunciado' => 'En el desarrollo web, ¿qué significan las siglas HTML?',
                'opcion_a' => 'High Tech Modern Language',
                'opcion_b' => 'HyperText Markup Language',
                'opcion_c' => 'HyperTransfer Machine Language',
                'opcion_d' => 'Home Tool Markup Language',
                'respuesta_correcta' => 'b',
            ],
            [
                'enunciado' => '¿Qué día del año se celebra tradicionalmente el "Día del Programador"?',
                'opcion_a' => 'El día 100 del año',
                'opcion_b' => 'El día 256 del año',
                'opcion_c' => 'El día 314 del año',
                'opcion_d' => 'El día 128 del año',
                'respuesta_correcta' => 'b',
            ],
            [
                'enunciado' => '¿Cuál de los siguientes NO es un lenguaje de programación ni de marcado, sino un motor de bases de datos relacional?',
                'opcion_a' => 'Python',
                'opcion_b' => 'Kotlin',
                'opcion_c' => 'PostgreSQL',
                'opcion_d' => 'Rust',
                'respuesta_correcta' => 'c',
            ],
        ];

        // 6. Banco de Preguntas: ETAPA 2 (Intermedias - 5 preguntas)
        $preguntasNivel2 = [
            [
                'enunciado' => '¿Quién creó originalmente tanto el kernel de Linux como el sistema de control de versiones Git?',
                'opcion_a' => 'Richard Stallman',
                'opcion_b' => 'Dennis Ritchie',
                'opcion_c' => 'Linus Torvalds',
                'opcion_d' => 'Ken Thompson',
                'respuesta_correcta' => 'c',
            ],
            [
                'enunciado' => 'En redes de datos, ¿qué puerto estándar utiliza por defecto el protocolo seguro HTTPS?',
                'opcion_a' => 'Puerto 80',
                'opcion_b' => 'Puerto 22',
                'opcion_c' => 'Puerto 443',
                'opcion_d' => 'Puerto 8080',
                'respuesta_correcta' => 'c',
            ],
            [
                'enunciado' => '¿Qué código de estado HTTP indica el famoso error "Too Many Requests" cuando un usuario envía demasiadas peticiones (Rate Limiting)?',
                'opcion_a' => '403 Forbidden',
                'opcion_b' => '429 Too Many Requests',
                'opcion_c' => '502 Bad Gateway',
                'opcion_d' => '418 I\'m a teapot',
                'respuesta_correcta' => 'b',
            ],
            [
                'enunciado' => 'En Inteligencia Artificial, ¿qué significan las siglas "LLM" en modelos como Gemini, GPT o Qwen?',
                'opcion_a' => 'Linear Logic Machine',
                'opcion_b' => 'Large Language Model',
                'opcion_c' => 'Local Learning Module',
                'opcion_d' => 'Logical Lexical Matrix',
                'respuesta_correcta' => 'b',
            ],
            [
                'enunciado' => '¿Qué estructura de datos sigue estrictamente el principio LIFO (Last In, First Out)?',
                'opcion_a' => 'Cola (Queue)',
                'opcion_b' => 'Pila (Stack)',
                'opcion_c' => 'Árbol Binario (Binary Tree)',
                'opcion_d' => 'Lista Enlazada (Linked List)',
                'respuesta_correcta' => 'b',
            ],
        ];

        // 7. Banco de Preguntas: ETAPA 3 (Difíciles - 5 preguntas)
        $preguntasNivel3 = [
            [
                'enunciado' => '¿Cómo se llama el artículo científico publicado en 2017 que introdujo la arquitectura "Transformer", base de toda la IA generativa moderna?',
                'opcion_a' => 'Computing Machinery and Intelligence',
                'opcion_b' => 'Attention Is All You Need',
                'opcion_c' => 'Deep Residual Learning for Image Recognition',
                'opcion_d' => 'Mastering the Game of Go without Human Knowledge',
                'respuesta_correcta' => 'b',
            ],
            [
                'enunciado' => 'En ciberseguridad y bases de datos, ¿cuál es el método más efectivo a nivel de código para prevenir ataques de Inyección SQL (SQLi)?',
                'opcion_a' => 'Ocultar los errores del navegador con CSS',
                'opcion_b' => 'Usar consultas preparadas (Prepared Statements) y parametrización',
                'opcion_c' => 'Cambiar el puerto de MySQL del 3306 al 3307',
                'opcion_d' => 'Encriptar las tablas con Base64',
                'respuesta_correcta' => 'b',
            ],
            [
                'enunciado' => 'Dentro del modelo OSI de redes, ¿en qué capa operan principalmente los enrutadores (Routers) y el direccionamiento IP?',
                'opcion_a' => 'Capa 2 (Enlace de datos)',
                'opcion_b' => 'Capa 3 (Red)',
                'opcion_c' => 'Capa 4 (Transporte)',
                'opcion_d' => 'Capa 7 (Aplicación)',
                'respuesta_correcta' => 'b',
            ],
            [
                'enunciado' => 'En sistemas distribuidos, el Teorema CAP establece que un sistema solo puede garantizar simultáneamente 2 de 3 propiedades. ¿Cuáles son?',
                'opcion_a' => 'Concurrencia, Autenticación y Persistencia',
                'opcion_b' => 'Consistencia, Disponibilidad (Availability) y Tolerancia al particionado',
                'opcion_c' => 'Capacidad, Ancho de banda y Procesamiento',
                'opcion_d' => 'Cifrado, Auditoría y Privacidad',
                'respuesta_correcta' => 'b',
            ],
            [
                'enunciado' => '¿Cuál es la complejidad temporal en notación Big-O de una Búsqueda Binaria (Binary Search) en un arreglo ordenado?',
                'opcion_a' => 'O(n)',
                'opcion_b' => 'O(n log n)',
                'opcion_c' => 'O(log n)',
                'opcion_d' => 'O(1)',
                'respuesta_correcta' => 'c',
            ],
        ];

        // 8. Banco de Preguntas: DESEMPATE / MUERTE SÚBITA (3 preguntas)
        $preguntasDesempate = [
            [
                'enunciado' => '¿En qué año fue lanzado oficialmente la primera versión del kernel de Linux por Linus Torvalds?',
                'opcion_a' => '1989',
                'opcion_b' => '1991',
                'opcion_c' => '1995',
                'opcion_d' => '1985',
                'respuesta_correcta' => 'b',
            ],
            [
                'enunciado' => '¿Cuántos bits componen exactamente una dirección IPv6?',
                'opcion_a' => '32 bits',
                'opcion_b' => '64 bits',
                'opcion_c' => '128 bits',
                'opcion_d' => '256 bits',
                'respuesta_correcta' => 'c',
            ],
            [
                'enunciado' => '¿Quién es considerada históricamente la primera programadora de la historia por su algoritmo para la máquina analítica de Charles Babbage?',
                'opcion_a' => 'Grace Hopper',
                'opcion_b' => 'Ada Lovelace',
                'opcion_c' => 'Margaret Hamilton',
                'opcion_d' => 'Hedy Lamarr',
                'respuesta_correcta' => 'b',
            ],
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