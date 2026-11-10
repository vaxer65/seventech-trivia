<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConfiguracionEvento;
use App\Models\Modulo;
use App\Models\Nivel;
use App\Models\Pregunta;
use App\Models\Equipo;
use App\Models\RespuestaEquipo;
use App\Models\Entrega;
use App\Models\HistorialEvento;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // La clave de admin se valida contra el hash de ADMIN_PASSWORD_HASH (config/app.php); nunca en el código

    public function index(Request $request)
    {
        $autorizado = $request->session()->get('es_admin_seventech', false);
        $config = ConfiguracionEvento::first();
        $modulos = Modulo::withCount('preguntas')->get();
        $niveles = Nivel::orderBy('orden_dificultad', 'asc')->get();

        // Ordenadas primero por Módulo, luego por Etapa (1 -> 2 -> 3 -> Desempate) y orden de creación
        $preguntas = Pregunta::select('preguntas.*')
            ->join('niveles', 'preguntas.nivel_id', '=', 'niveles.id')
            ->with(['modulo', 'nivel'])
            ->orderBy('preguntas.modulo_id', 'asc')
            ->orderBy('niveles.orden_dificultad', 'asc')
            ->orderBy('preguntas.id', 'asc')
            ->get();

        $equipos = Equipo::with('integrantes')->orderBy('puntaje_total', 'desc')->orderBy('tiempo_acumulado_ms', 'asc')->get();

        $historial = HistorialEvento::latest()->get();

        return view('admin.index', compact('autorizado', 'config', 'modulos', 'niveles', 'preguntas', 'equipos', 'historial'));
    }

    public function login(Request $request)
    {
        $hash = (string) config('app.admin_password_hash');

        if ($hash !== '' && Hash::check((string) $request->input('password'), $hash)) {
            $request->session()->regenerate();
            $request->session()->put('es_admin_seventech', true);
            return redirect()->route('admin.index');
        }
        return back()->withErrors(['password' => 'Contraseña de administrador incorrecta.']);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('es_admin_seventech');
        return redirect()->route('admin.index');
    }

    // Cambiar estado del juego, tiempo global (minutos) o módulo activo
    public function actualizarConfiguracion(Request $request)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        $config = ConfiguracionEvento::first();

        $validated = $request->validate([
            'estado_juego'           => 'required|in:registro,en_curso,pausado,desempate,finalizado',
            'modulo_activo_id'       => 'nullable|exists:modulos,id',
            'max_integrantes_equipo' => 'required|integer|between:2,10',
            'max_equipos_permitidos' => 'required|integer|between:1,200',
            'tiempo_total_minutos'   => 'nullable|integer|between:1,180',
        ]);

        $estadoAnterior = $config->estado_juego;
        $nuevoEstado = $validated['estado_juego'];
        $minutosTotales = (int) ($validated['tiempo_total_minutos'] ?? $config->tiempo_total_minutos ?? 15);

        $datosActualizar = [
            'estado_juego'           => $nuevoEstado,
            'modulo_activo_id'       => $validated['modulo_activo_id'] ?? null,
            'max_integrantes_equipo' => (int) $validated['max_integrantes_equipo'],
            'max_equipos_permitidos' => (int) $validated['max_equipos_permitidos'],
            'tiempo_total_minutos'   => $minutosTotales,
        ];

        // Si el evento pasa a 'en_curso' desde 'registro', arrancamos el reloj global y sorteamos preguntas nuevas
        if ($nuevoEstado === 'en_curso' && $estadoAnterior === 'registro') {
            $datosActualizar['fin_evento_at'] = now()->addMinutes($minutosTotales);
            $datosActualizar['pausado_at'] = null;
            $datosActualizar['semilla_partida'] = \Illuminate\Support\Str::random(16);
        } elseif ($nuevoEstado === 'en_curso' && $estadoAnterior === 'pausado' && $config->fin_evento_at && $config->pausado_at) {
            // S3: Al reanudar desde pausa, compensamos los segundos que estuvo pausado
            $segundosEnPausa = max(0, (int) $config->pausado_at->diffInSeconds(now(), false));
            $datosActualizar['fin_evento_at'] = $config->fin_evento_at->copy()->addSeconds($segundosEnPausa);
            $datosActualizar['pausado_at'] = null;
        } elseif ($nuevoEstado === 'en_curso' && !$config->fin_evento_at) {
            $datosActualizar['fin_evento_at'] = now()->addMinutes($minutosTotales);
            $datosActualizar['pausado_at'] = null;
        } elseif ($nuevoEstado === 'pausado' && $estadoAnterior !== 'pausado') {
            $datosActualizar['pausado_at'] = now();
        } elseif ($nuevoEstado === 'registro') {
            $datosActualizar['fin_evento_at'] = null;
            $datosActualizar['pausado_at'] = null;
        }

        $config->update($datosActualizar);

        return back()->with('ok', 'Configuración y tiempos del evento actualizados en vivo.');
    }
    // Modificar cantidad de preguntas, puntos o tiempo de cualquier Etapa/Nivel (Escalabilidad)
    public function actualizarNivel(Request $request, $id)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        $validated = $request->validate([
            'nombre'             => 'required|string|max:100',
            'cantidad_preguntas' => 'required|integer|between:1,50',
            'puntos_por_acierto' => 'required|integer|between:1,10000',
            'tiempo_limite_seg'  => 'required|integer|between:5,300',
        ]);

        $nivel = Nivel::findOrFail($id);
        $nivel->update($validated);

        return back()->with('ok', 'Etapa actualizada correctamente.');
    }

    // Agregar cualquier módulo/categoría nuevo
    public function crearModulo(Request $request)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        $request->validate(['nombre' => 'required|string|max:80']);
        Modulo::create([
            'nombre'      => $request->nombre,
            'descripcion' => $request->descripcion,
            'activo'      => true,
        ]);

        return back()->with('ok', 'Nuevo módulo creado.');
    }

    // Agregar nuevas preguntas a cualquier módulo y etapa
    public function crearPregunta(Request $request)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        $validated = $request->validate([
            'modulo_id'          => 'required|exists:modulos,id',
            'nivel_id'           => 'required|exists:niveles,id',
            'enunciado'          => 'required|string',
            'opcion_a'           => 'required|string',
            'opcion_b'           => 'required|string',
            'opcion_c'           => 'required|string',
            'opcion_d'           => 'required|string',
            'respuesta_correcta' => 'required|in:a,b,c,d',
        ]);

        Pregunta::create($validated);
        return back()->with('ok', 'Pregunta agregada al banco exitosamente.');
    }

    // Activar Ronda de Desempate (Muerte Súbita) entre los equipos punteros
    public function activarDesempate(Request $request)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        $request->validate([
            'equipos_desempate'   => 'nullable|array',
            'equipos_desempate.*' => 'integer|exists:equipos,id',
        ]);

        Equipo::query()->update(['en_desempate' => false]);

        if ($request->filled('equipos_desempate')) {
            // Si el Admin seleccionó manualmente qué equipos empataron
            Equipo::whereIn('id', $request->equipos_desempate)->update(['en_desempate' => true]);
        } else {
            // Detección automática: toma el puntaje más alto y mete a los que tengan ese puntaje (o el top 2)
            $maxPuntaje = Equipo::max('puntaje_total');
            $empatados = Equipo::where('puntaje_total', $maxPuntaje)->pluck('id');

            if ($empatados->count() < 2) {
                $empatados = Equipo::orderBy('puntaje_total', 'desc')->orderBy('tiempo_acumulado_ms', 'asc')->take(2)->pluck('id');
            }
            Equipo::whereIn('id', $empatados)->update(['en_desempate' => true]);
        }

        ConfiguracionEvento::first()->update(['estado_juego' => 'desempate']);

        return back()->with('ok', '¡Muerte Súbita activada para los equipos seleccionados!');
    }

   // Reiniciar puntajes y sortear un nuevo grupo aleatorio de preguntas para la siguiente partida
    public function reiniciarJuego(Request $request)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        RespuestaEquipo::truncate();
        Entrega::truncate();
        if ($request->boolean('borrar_equipos')) {
            Equipo::query()->delete();
        } else {
            Equipo::query()->update([
                'fase_actual'           => 1,
                'preguntas_contestadas' => 0,
                'aciertos_totales'      => 0,
                'puntaje_total'         => 0,
                'tiempo_acumulado_ms'   => 0,
                'en_desempate'          => false,
                'finalizo_carrera'      => false,
            ]);
        }

        ConfiguracionEvento::first()->update([
            'estado_juego'    => 'registro',
            'fin_evento_at'   => null,
            'pausado_at'      => null,
            // Nueva semilla aleatoria para que en la nueva partida salgan 5 preguntas distintas del banco
            'semilla_partida' => \Illuminate\Support\Str::random(16),
        ]);

        return back()->with('ok', 'Sistema reiniciado y nuevas preguntas aleatorias sorteadas.');
    }
    // Generar equipos de prueba con nombres reales para ensayar el flujo completo
    public function generarEquiposPrueba(Request $request)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);
        abort_unless(app()->isLocal(), 404); // Simulador solo en entorno local

        $equiposDemo = [
            ['nombre' => 'CyberTitans UEB',    'color' => '#10B981', 'miembros' => ['Mateo Silva', 'Camila Paredes']],
            ['nombre' => 'Linuxeros Guaranda', 'color' => '#00E5FF', 'miembros' => ['Andrés Gavilánez', 'Sofía Lara']],
            ['nombre' => 'NullPointers',       'color' => '#A855F7', 'miembros' => ['Diego Velasco', 'Valeria Ortiz']],
            ['nombre' => 'Quantum Coders',     'color' => '#F59E0B', 'miembros' => ['Sebastián Mora', 'Daniela Paz']],
            ['nombre' => 'RedHat Squad',       'color' => '#EC4899', 'miembros' => ['Alejandro Cruz', 'Fernanda Ríos']],
        ];

        foreach ($equiposDemo as $item) {
            if (!Equipo::where('nombre_equipo', $item['nombre'])->exists()) {
                $eq = Equipo::create([
                    'nombre_equipo'    => $item['nombre'],
                    'token_sesion'     => \Illuminate\Support\Str::random(60),
                    'pin_recuperacion' => str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT),
                    'color_carril'     => $item['color'],
                    'fase_actual'      => 1,
                ]);
                foreach ($item['miembros'] as $nombreReal) {
                    \App\Models\Integrante::create([
                        'equipo_id'       => $eq->id,
                        'nombre_completo' => $nombreReal,
                    ]);
                }
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }
        return back()->with('ok', '¡5 equipos de prueba con integrantes registrados en la línea de salida!');
    }

    // Simular que todos los equipos avanzan 1 pregunta en vivo
    public function simularAvanceCarrera(Request $request)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);
        abort_unless(app()->isLocal(), 404); // Simulador solo en entorno local

        $config = ConfiguracionEvento::first();
        if ($config->estado_juego === 'registro') {
            $config->update([
                'estado_juego'  => 'en_curso',
                'fin_evento_at' => now()->addMinutes($config->tiempo_total_minutos ?? 15),
            ]);
        }

        $niveles = Nivel::where('es_desempate', false)->orderBy('orden_dificultad', 'asc')->get();
        $equipos = Equipo::all();
        $algunoAvanzo = false;

        foreach ($equipos as $equipo) {
            $respondioTurno = false;
            foreach ($niveles as $nivel) {
                $contestadas = RespuestaEquipo::where('equipo_id', $equipo->id)->where('nivel_id', $nivel->id)->count();
                if ($contestadas < $nivel->cantidad_preguntas) {
                    $respondidasIds = RespuestaEquipo::where('equipo_id', $equipo->id)->pluck('pregunta_id');
                    $pregunta = Pregunta::where('nivel_id', $nivel->id)->whereNotIn('id', $respondidasIds)->first();

                    if ($pregunta) {
                        $acierta = (rand(1, 100) <= 72);
                        $tiempoMs = rand(2200, 13500);
                        $puntos = $acierta ? $nivel->puntos_por_acierto : 0;

                        RespuestaEquipo::create([
                            'equipo_id'           => $equipo->id,
                            'pregunta_id'         => $pregunta->id,
                            'nivel_id'            => $nivel->id,
                            'opcion_elegida'      => $acierta ? $pregunta->respuesta_correcta : 'a',
                            'es_correcta'         => $acierta,
                            'puntos_ganados'      => $puntos,
                            'tiempo_respuesta_ms' => $tiempoMs,
                        ]);

                        $equipo->increment('preguntas_contestadas');
                        $equipo->increment('tiempo_acumulado_ms', $tiempoMs);
                        $equipo->update(['fase_actual' => $nivel->orden_dificultad]);

                        if ($acierta) {
                            $equipo->increment('aciertos_totales');
                            $equipo->increment('puntaje_total', $puntos);
                        }
                        $algunoAvanzo = true;
                        $respondioTurno = true;
                    }
                    break;
                }
            }

            if (!$respondioTurno && !$equipo->finalizo_carrera) {
                $equipo->update(['finalizo_carrera' => true]);
            }
        }

        // Si todos terminaron todas las preguntas en la simulación, pasamos a 'finalizado' para mostrar el Podio
        if (!$algunoAvanzo && $equipos->count() > 0) {
            $config->update(['estado_juego' => 'finalizado']);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'ok'            => true,
                'alguno_avanzo' => $algunoAvanzo,
                'estado_juego'  => $config->fresh()->estado_juego,
            ]);
        }

        return back()->with('ok', '¡Avance simulado en vivo! Revisa la pantalla de Espectador.');
    }
    // Editar cualquier pregunta existente en vivo
    public function actualizarPregunta(Request $request, $id)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        $validated = $request->validate([
            'modulo_id'          => 'sometimes|required|exists:modulos,id',
            'nivel_id'           => 'sometimes|required|exists:niveles,id',
            'enunciado'          => 'required|string',
            'opcion_a'           => 'required|string',
            'opcion_b'           => 'required|string',
            'opcion_c'           => 'required|string',
            'opcion_d'           => 'required|string',
            'respuesta_correcta' => 'required|in:a,b,c,d',
        ]);

        $pregunta = Pregunta::findOrFail($id);
        $pregunta->update($validated);

        return back()->with('ok', 'Pregunta actualizada correctamente en la base de datos.');
    }

    // Eliminar una pregunta del banco
    public function eliminarPregunta(Request $request, $id)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        Pregunta::findOrFail($id)->delete();
        return back()->with('ok', 'Pregunta eliminada del banco.');
    }

    // Eliminar un módulo completo
    public function eliminarModulo(Request $request, $id)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        if (Modulo::count() <= 1) {
            return back()->with('ok', 'Debe existir al menos 1 módulo en el sistema.');
        }

        Modulo::findOrFail($id)->delete();
        return back()->with('ok', 'Módulo eliminado.');
    }
    // Eliminar un equipo específico desde el panel de administración
    public function eliminarEquipo(Request $request, $id)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        Equipo::findOrFail($id)->delete();
        return back()->with('ok', 'Equipo eliminado correctamente.');
    }
    // Guardar los resultados actuales en el Salón de la Fama / Historial de Eventos
    public function guardarHistorialEvento(Request $request)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        $config = ConfiguracionEvento::with('moduloActivo')->first();
        $equipos = Equipo::with('integrantes')
            ->orderBy('puntaje_total', 'desc')
            ->orderBy('aciertos_totales', 'desc')
            ->orderBy('tiempo_acumulado_ms', 'asc')
            ->get();

    if ($equipos->isEmpty()) {
        return back()->with('ok', 'No hay equipos registrados para guardar en el historial.');
    }

    $nombreEdicion = trim($request->nombre_edicion ?: ($config->nombre_evento . ' - ' . now()->format('d/m/Y H:i')));

    $resultados = $equipos->values()->map(function ($eq, $idx) {
        return [
            'posicion'        => $idx + 1,
            'nombre_equipo'   => $eq->nombre_equipo,
            'puntaje_total'   => $eq->puntaje_total,
            'aciertos_totales'=> $eq->aciertos_totales,
            'tiempo_segundos' => round($eq->tiempo_acumulado_ms / 1000, 2),
            // Se guarda para el Admin, pero en la vista pública solo se muestra el nombre del equipo
            'integrantes'     => $eq->integrantes->pluck('nombre_completo')->toArray(),
          ];
        })->toArray();

        HistorialEvento::create([
            'nombre_edicion'       => $nombreEdicion,
            'modulo_jugado'        => $config->moduloActivo->nombre ?? 'General Tech',
            'total_equipos'        => count($resultados),
            'podio_top3'           => array_slice($resultados, 0, 3),
            'resultados_completos' => $resultados,
        ]);

        return back()->with('ok', '¡Resultados archivados para siempre en el Historial de Eventos!');
    }

    public function eliminarHistorialEvento(Request $request, $id)
    {   
        if (!$request->session()->get('es_admin_seventech')) abort(403);
        HistorialEvento::findOrFail($id)->delete();
        return back()->with('ok', 'Registro eliminado del historial.');
    }
}