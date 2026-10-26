<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\ConfiguracionEvento;
use App\Models\Equipo;
use App\Models\Integrante;
use App\Models\Nivel;
use App\Models\Pregunta;
use App\Models\RespuestaEquipo;

class ConcursanteController extends Controller
{
    // Vista principal del concursante
    public function index(Request $request)
    {
        $config = ConfiguracionEvento::first();
        $token = $request->session()->get('equipo_token');
        $equipo = $token ? Equipo::with('integrantes')->where('token_sesion', $token)->first() : null;

        return view('concursante.index', compact('config', 'equipo'));
    }

    // Registro blindado de equipo (Nombre único + mín 2 y máx 5 integrantes)
    public function registrarEquipo(Request $request)
    {
        $config = ConfiguracionEvento::first();

        if ($config->estado_juego !== 'registro' && $config->estado_juego !== 'en_curso') {
            return response()->json(['error' => 'El registro de equipos está cerrado en este momento.'], 403);
        }

        if (Equipo::count() >= $config->max_equipos_permitidos) {
            return response()->json(['error' => 'Se alcanzó el límite máximo de equipos permitidos.'], 403);
        }

        // Filtramos casillas vacías directamente dentro del request
        $request->merge([
            'integrantes' => array_values(array_filter((array) $request->input('integrantes', []), function ($valor) {
                return trim((string) $valor) !== '';
            }))
        ]);

        $request->validate([
            'nombre_equipo' => 'required|string|max:40|unique:equipos,nombre_equipo',
            'integrantes'   => 'required|array|min:2|max:' . $config->max_integrantes_equipo,
            'integrantes.*' => 'required|string|max:60',
        ], [
            'nombre_equipo.unique' => '¡Ese nombre de equipo ya está registrado! Elige otro.',
            'integrantes.min'      => 'El equipo debe tener como mínimo 2 integrantes para participar.',
            'integrantes.max'      => 'El máximo permitido es de ' . $config->max_integrantes_equipo . ' integrantes.',
        ]);

        $coloresPista = ['#00E5FF', '#10B981', '#F59E0B', '#38BDF8', '#A855F7', '#EC4899', '#22D3EE'];
        $token = Str::random(60);

        $pin = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

        $equipo = Equipo::create([
            'nombre_equipo'    => strip_tags(trim($request->nombre_equipo)),
            'token_sesion'     => $token,
            'pin_recuperacion' => $pin,
            'color_carril'     => $coloresPista[Equipo::count() % count($coloresPista)],
            'fase_actual'      => 1,
        ]);
        foreach ($request->integrantes as $nombre) {
            Integrante::create([
                'equipo_id'       => $equipo->id,
                'nombre_completo' => strip_tags(trim($nombre)),
            ]);
        }

        $request->session()->put('equipo_token', $token);

        return response()->json([
            'mensaje' => '¡Equipo registrado con éxito!',
            'equipo'  => $equipo->load('integrantes')
        ]);
    }

    // Obtener el estado actual y la siguiente pregunta (SIN respuesta correcta)
    public function obtenerEstadoYPregunta(Request $request)
    {
        $token = $request->session()->get('equipo_token');
        $equipo = $token ? Equipo::where('token_sesion', $token)->first() : null;

        if (!$equipo) {
            return response()->json(['autenticado' => false]);
        }

        $config = ConfiguracionEvento::first();

        // Si el evento está en espera o pausado
        if (in_array($config->estado_juego, ['registro', 'pausado', 'finalizado'])) {
            return response()->json([
                'autenticado'  => true,
                'estado_juego' => $config->estado_juego,
                'equipo'       => $equipo,
                'pregunta'     => null,
            ]);
        }

        // Si estamos en RONDA DE DESEMPATE (Muerte Súbita)
        if ($config->estado_juego === 'desempate') {
            if (!$equipo->en_desempate) {
                return response()->json([
                    'autenticado'  => true,
                    'estado_juego' => 'esperando_desempate',
                    'equipo'       => $equipo,
                ]);
            }

            $nivelDesempate = Nivel::where('es_desempate', true)->first();
            return $this->entregarPreguntaDeNivel($equipo, $nivelDesempate, $config);
        }

        // MODO CARRERA NORMAL: Recorremos los niveles en orden de dificultad (1 -> 2 -> 3)
        $niveles = Nivel::where('es_desempate', false)->orderBy('orden_dificultad', 'asc')->get();

        foreach ($niveles as $nivel) {
            $contestadasEnNivel = RespuestaEquipo::where('equipo_id', $equipo->id)
                ->where('nivel_id', $nivel->id)
                ->count();

            if ($contestadasEnNivel < $nivel->cantidad_preguntas) {
                // Actualizamos la etapa actual en la que corre el equipo
                if ($equipo->fase_actual !== $nivel->orden_dificultad) {
                    $equipo->update(['fase_actual' => $nivel->orden_dificultad]);
                }

                return $this->entregarPreguntaDeNivel($equipo, $nivel, $config, $contestadasEnNivel + 1);
            }
        }

        // Si ya completó todas las etapas de la carrera
        if (!$equipo->finalizo_carrera) {
            $equipo->update(['finalizo_carrera' => true]);
        }

        return response()->json([
            'autenticado'  => true,
            'estado_juego' => 'carrera_completada',
            'equipo'       => $equipo,
        ]);
    }

    private function entregarPreguntaDeNivel($equipo, $nivel, $config, $numeroPreguntaNivel = 1)
    {
        $respondidasIds = RespuestaEquipo::where('equipo_id', $equipo->id)
            ->pluck('pregunta_id')
            ->toArray();

        $query = Pregunta::where('nivel_id', $nivel->id)
            ->where('activa', true)
            ->whereNotIn('id', $respondidasIds);

        if ($config->modulo_activo_id) {
            $query->where('modulo_id', $config->modulo_activo_id);
        }

        // Orden determinista por equipo para que si refresca la página vea la misma pregunta actual
        $pregunta = $query->orderByRaw('CRC32(CONCAT(id, ?))', [$equipo->id])->first();

        if (!$pregunta) {
            return response()->json([
                'autenticado'  => true,
                'estado_juego' => 'carrera_completada',
                'equipo'       => $equipo,
            ]);
        }

        return response()->json([
            'autenticado'  => true,
            'estado_juego' => $config->estado_juego,
            'equipo'       => $equipo,
            'nivel'        => $nivel,
            'progreso_fase'=> $numeroPreguntaNivel . ' / ' . $nivel->cantidad_preguntas,
            'pregunta'     => $pregunta, // Recuerda: el modelo Pregunta oculta 'respuesta_correcta'
        ]);
    }

    // Recibir respuesta, calificar en secreto y avanzar siempre
    public function enviarRespuesta(Request $request)
    {
        $token = $request->session()->get('equipo_token');
        $equipo = $token ? Equipo::where('token_sesion', $token)->first() : null;

        if (!$equipo) {
            return response()->json(['error' => 'Sesión no válida'], 401);
        }

        $request->validate([
            'pregunta_id'         => 'required|exists:preguntas,id',
            'opcion_elegida'      => 'nullable|in:a,b,c,d',
            'tiempo_respuesta_ms' => 'required|integer|min:100|max:120000',
        ]);

        // Candado Anti-Sabotaje: Verificar si ya fue respondida
        $yaRespondio = RespuestaEquipo::where('equipo_id', $equipo->id)
            ->where('pregunta_id', $request->pregunta_id)
            ->exists();

        if ($yaRespondio) {
            return response()->json(['error' => 'Esta pregunta ya fue procesada.'], 422);
        }

        $pregunta = Pregunta::with('nivel')->findOrFail($request->pregunta_id);
        $opcion = $request->opcion_elegida ? strtolower($request->opcion_elegida) : null;

        // Calificación interna en el servidor
        $esCorrecta = ($opcion !== null && $opcion === strtolower($pregunta->respuesta_correcta));
        $puntosGanados = $esCorrecta ? $pregunta->nivel->puntos_por_acierto : 0;

        RespuestaEquipo::create([
            'equipo_id'           => $equipo->id,
            'pregunta_id'         => $pregunta->id,
            'nivel_id'            => $pregunta->nivel_id,
            'opcion_elegida'      => $opcion,
            'es_correcta'         => $esCorrecta,
            'puntos_ganados'      => $puntosGanados,
            'tiempo_respuesta_ms' => $request->tiempo_respuesta_ms,
        ]);

        // El equipo SIEMPRE avanza en preguntas_contestadas aunque falle, pero solo suma puntos si acierta
        $equipo->increment('preguntas_contestadas');
        $equipo->increment('tiempo_acumulado_ms', $request->tiempo_respuesta_ms);

        if ($esCorrecta) {
            $equipo->increment('aciertos_totales');
            $equipo->increment('puntaje_total', $puntosGanados);
        }

        return response()->json([
            'procesado' => true,
            'mensaje'   => 'Respuesta registrada. ¡Avanzando a la siguiente etapa!',
        ]);
    }
    // Permitir cerrar sesión del equipo actual para registrar otro en pruebas
    public function salirEquipo(Request $request)
    {
        $request->session()->forget('equipo_token');
        return redirect()->route('concursante.index');
    }
    // Recuperar sesión de equipo en caso de desconexión o cambio de celular
    public function recuperarEquipo(Request $request)
    {
        $request->validate([
            'nombre_equipo'    => 'required|string',
            'pin_recuperacion' => 'required|string|size:4',
        ]);

        $equipo = Equipo::where('nombre_equipo', trim($request->nombre_equipo))
            ->where('pin_recuperacion', trim($request->pin_recuperacion))
            ->first();

        if (!$equipo) {
            return response()->json([
                'error' => 'Nombre de equipo o PIN de 4 dígitos incorrecto.'
            ], 422);
        }

        $request->session()->put('equipo_token', $equipo->token_sesion);

        return response()->json([
            'mensaje' => '¡Sesión de equipo recuperada con éxito!',
            'equipo'  => $equipo->load('integrantes')
        ]);
    }
}