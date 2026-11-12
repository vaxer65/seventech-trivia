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

        // 2. 10 Módulos Temáticos de Tecnología & Programación
        $modulosDef = [
            'Fundamentos de Programación & Lógica' => [
                'descripcion' => 'Variables, bucles, condicionales, funciones y algoritmos básicos',
                'icono' => 'code',
            ],
            'Desarrollo Web Frontend (HTML, CSS, JS)' => [
                'descripcion' => 'HTML5, CSS3, DOM, JavaScript moderno, maquetación y navegadores',
                'icono' => 'globe',
            ],
            'Backend, APIs, PHP & Laravel' => [
                'descripcion' => 'Arquitectura web, MVC, PHP 8, Laravel, REST APIs y microservicios',
                'icono' => 'server',
            ],
            'Bases de Datos Relacionales & SQL' => [
                'descripcion' => 'Modelado relacional, normalización, SQL, transacciones ACID e índices',
                'icono' => 'database',
            ],
            'Estructuras de Datos, Algoritmos & POO' => [
                'descripcion' => 'Pilas, colas, árboles, grafos, Big-O, patrones de diseño y POO',
                'icono' => 'cpu',
            ],
            'Sistemas Operativos, Linux & DevOps' => [
                'descripcion' => 'Kernel Linux, bash, permisos, procesos, Docker, CI/CD y despliegue',
                'icono' => 'terminal',
            ],
            'Redes de Datos, Protocolos & Cloud' => [
                'descripcion' => 'Modelo OSI, TCP/IP, DNS, HTTP/S, subredes y servicios en la nube',
                'icono' => 'cloud',
            ],
            'Ciberseguridad, Hacking Ético & Criptografía' => [
                'descripcion' => 'OWASP Top 10, criptografía simétrica/asimétrica, autenticación y seguridad',
                'icono' => 'shield',
            ],
            'Inteligencia Artificial, Datos & Tendencias Tech' => [
                'descripcion' => 'Machine Learning, redes neuronales, Transformers, LLMs y ciencia de datos',
                'icono' => 'bot',
            ],
            'Cultura Geek, Historia de la Computación & Gaming' => [
                'descripcion' => 'Hitos históricos de la computación, pioneros tech, curiosidades y cultura gamer',
                'icono' => 'gamepad',
            ],
        ];

        $moduloIds = [];
        foreach ($modulosDef as $nombre => $datos) {
            $modulo = Modulo::create([
                'nombre'      => $nombre,
                'descripcion' => $datos['descripcion'],
                'icono'       => $datos['icono'],
                'activo'      => true,
            ]);
            $moduloIds[$nombre] = $modulo->id;
        }

        // 3. Configuración Inicial del Evento (Modo "Todos los Módulos" activo por defecto con modulo_activo_id = null)
        ConfiguracionEvento::create([
            'nombre_evento'          => 'Seven Tech Trivia 2026',
            'max_integrantes_equipo' => 5,
            'max_equipos_permitidos' => 30,
            'tiempo_total_minutos'   => 15,
            'semilla_partida'        => Str::random(16),
            'estado_juego'           => 'registro',
            'modulo_activo_id'       => null,
        ]);

        // 4. Niveles de Dificultad (5 preguntas por etapa, 3 en muerte súbita)
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

        // 5. Carga de los 4 datasets (360 preguntas reales exhaustivas y balanceadas)
        $etapa1 = require __DIR__ . '/data/etapa1_faciles.php';
        $etapa2 = require __DIR__ . '/data/etapa2_medias.php';
        $etapa3 = require __DIR__ . '/data/etapa3_dificiles.php';
        $muerteSubita = require __DIR__ . '/data/muerte_subita.php';

        $datasets = [
            [$etapa1, $nivel1->id],
            [$etapa2, $nivel2->id],
            [$etapa3, $nivel3->id],
            [$muerteSubita, $nivelDesempate->id],
        ];

        $now = now();
        $rows = [];

        foreach ($datasets as [$preguntas, $nivelId]) {
            foreach ($preguntas as $p) {
                $moduloNombre = $p['modulo'];
                if (! isset($moduloIds[$moduloNombre])) {
                    throw new \RuntimeException("Módulo no reconocido en dataset: {$moduloNombre}");
                }

                $rows[] = [
                    'modulo_id'          => $moduloIds[$moduloNombre],
                    'nivel_id'           => $nivelId,
                    'enunciado'          => $p['enunciado'],
                    'opcion_a'           => $p['opcion_a'],
                    'opcion_b'           => $p['opcion_b'],
                    'opcion_c'           => $p['opcion_c'],
                    'opcion_d'           => $p['opcion_d'],
                    'respuesta_correcta' => $p['respuesta_correcta'],
                    'activa'             => true,
                    'es_amena'           => !empty($p['es_amena']),
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ];
            }
        }

        // Inserción en lotes de 50 para máxima velocidad y eficiencia
        foreach (array_chunk($rows, 50) as $chunk) {
            Pregunta::insert($chunk);
        }
    }
}
