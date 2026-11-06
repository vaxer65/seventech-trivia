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

    // Registro blindado: Nombre de equipo único + Nombres y Apellidos reales irrepetibles
    public function registrarEquipo(Request $request)
    {
        $config = ConfiguracionEvento::first();

        if ($config->estado_juego !== 'registro' && $config->estado_juego !== 'en_curso') {
            return response()->json(['error' => 'El registro de equipos está cerrado en este momento.'], 403);
        }

        if (Equipo::count() >= $config->max_equipos_permitidos) {
            return response()->json(['error' => 'Se alcanzó el límite máximo de equipos permitidos.'], 403);
        }

        // Limpiamos espacios dobles y casillas vacías
        $integrantesLimpios = array_values(array_filter(array_map(function ($val) {
            return preg_replace('/\s+/', ' ', strip_tags(trim((string) $val)));
        }, (array) $request->input('integrantes', [])), fn($v) => $v !== ''));

        $request->merge([
            'nombre_equipo' => preg_replace('/\s+/', ' ', strip_tags(trim((string) $request->nombre_equipo))),
            'integrantes'   => $integrantesLimpios,
        ]);

        $request->validate([
            'nombre_equipo' => 'required|string|min:2|max:40|unique:equipos,nombre_equipo',
            'integrantes'   => 'required|array|min:2|max:' . $config->max_integrantes_equipo,
            // Exige mínimo Nombre y Apellido (al menos dos palabras con letras)
            'integrantes.*' => ['required', 'string', 'min:5', 'max:60', 'regex:/^\S+\s+\S+.*$/u'],
        ], [
            'nombre_equipo.unique' => '¡Ese nombre de equipo ya está en uso! Elige otro.',
            'integrantes.min'      => 'El equipo debe tener como mínimo 2 integrantes.',
            'integrantes.max'      => 'El máximo permitido es de ' . $config->max_integrantes_equipo . ' integrantes.',
            'integrantes.*.regex'  => 'Ingresa el Nombre y Apellido real de cada integrante (mínimo nombre y un apellido).',
            'integrantes.*.min'    => 'Cada integrante debe registrar su nombre y apellido real.',
        ]);

        // 1. Verificar que no hayan escrito dos veces a la misma persona dentro del mismo equipo
        $normalizados = array_map(fn($n) => mb_strtolower($n, 'UTF-8'), $integrantesLimpios);
        if (count($normalizados) !== count(array_unique($normalizados))) {
            return response()->json([
                'error' => 'No puedes repetir el mismo nombre de integrante dentro de tu equipo.'
            ], 422);
        }

        // 2. Verificar que ningún integrante esté ya inscrito en otro equipo diferente
        foreach ($integrantesLimpios as $nombreReal) {
            $yaExiste = Integrante::whereRaw('LOWER(nombre_completo) = ?', [mb_strtolower($nombreReal, 'UTF-8')])->first();
            if ($yaExiste) {
                return response()->json([
                    'error' => 'El participante "' . $nombreReal . '" ya se encuentra registrado en otro equipo.'
                ], 422);
            }
        }

        $coloresPista = ['#00E5FF', '#10B981', '#F59E0B', '#38BDF8', '#A855F7', '#EC4899', '#22D3EE'];
        $token = Str::random(60);
        $pin = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

        $equipo = Equipo::create([
            'nombre_equipo'    => $request->nombre_equipo,
            'token_sesion'     => $token,
            'pin_recuperacion' => $pin,
            'color_carril'     => $coloresPista[Equipo::count() % count($coloresPista)],
            'fase_actual'      => 1,
        ]);

        foreach ($integrantesLimpios as $nombreReal) {
            Integrante::create([
                'equipo_id'       => $equipo->id,
                'nombre_completo' => ucwords(mb_strtolower($nombreReal, 'UTF-8')),
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

        // Verificar si se agotó el tiempo global de toda la trivia
        if ($config->estado_juego === 'en_curso' && $config->fin_evento_at && now()->greaterThanOrEqualTo($config->fin_evento_at)) {
            $config->update(['estado_juego' => 'finalizado']);
        }

        // Si el evento está en espera, pausado o finalizado
        if (in_array($config->estado_juego, ['registro', 'pausado', 'finalizado'])) {
            return response()->json([
                'autenticado'               => true,
                'estado_juego'              => $config->estado_juego,
                'segundos_globales'         => $config->segundos_globales_restantes,
                'equipo'                    => $equipo,
                'pregunta'                  => null,
            ]);
        }

        // Si estamos en RONDA DE DESEMPATE (Muerte Súbita)
        if ($config->estado_juego === 'desempate') {
            if (!$equipo->en_desempate) {
                return response()->json([
                    'autenticado'       => true,
                    'estado_juego'      => 'esperando_desempate',
                    'segundos_globales' => null,
                    'equipo'            => $equipo,
                ]);
            }

            $nivelDesempate = Nivel::where('es_desempate', true)->first();
            $contestadasDesempate = RespuestaEquipo::where('equipo_id', $equipo->id)
                ->where('nivel_id', $nivelDesempate->id)
                ->count();

            if ($contestadasDesempate >= $nivelDesempate->cantidad_preguntas) {
                return response()->json([
                    'autenticado'  => true,
                    'estado_juego' => 'carrera_completada',
                    'equipo'       => $equipo,
                ]);
            }

            return $this->entregarPreguntaDeNivel($equipo, $nivelDesempate, $config, $contestadasDesempate + 1);
        }

        // MODO CARRERA NORMAL: Recorremos los niveles en orden de dificultad (1 -> 2 -> 3)
        // Cada fase toma su límite configurado (por defecto 5 preguntas de las 100 disponibles)
        $niveles = Nivel::where('es_desempate', false)->orderBy('orden_dificultad', 'asc')->get();

        foreach ($niveles as $nivel) {
            $contestadasEnNivel = RespuestaEquipo::where('equipo_id', $equipo->id)
                ->where('nivel_id', $nivel->id)
                ->count();

            if ($contestadasEnNivel < $nivel->cantidad_preguntas) {
                if ($equipo->fase_actual !== $nivel->orden_dificultad) {
                    $equipo->update(['fase_actual' => $nivel->orden_dificultad]);
                }

                return $this->entregarPreguntaDeNivel($equipo, $nivel, $config, $contestadasEnNivel + 1);
            }
        }

        if (!$equipo->finalizo_carrera) {
            $equipo->update(['finalizo_carrera' => true]);
        }

        return response()->json([
            'autenticado'       => true,
            'estado_juego'      => 'carrera_completada',
            'segundos_globales' => $config->segundos_globales_restantes,
            'equipo'            => $equipo,
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

        // SELECCIÓN ALEATORIA COMPARTIDA:
        // Usa 'semilla_partida' para escoger al azar dentro del banco de 50-100 preguntas,
        // pero garantizando que TODOS los equipos reciban exactamente las mismas preguntas en esa partida.
        $semilla = $config->semilla_partida ?? 'seventech2026';
        $pregunta = $query->orderByRaw('CRC32(CONCAT(id, ?))', [$semilla])->first();

        if (!$pregunta) {
            return response()->json([
                'autenticado'       => true,
                'estado_juego'      => 'carrera_completada',
                'segundos_globales' => $config->segundos_globales_restantes,
                'equipo'            => $equipo,
            ]);
        }

        return response()->json([
            'autenticado'       => true,
            'estado_juego'      => $config->estado_juego,
            'segundos_globales' => $config->segundos_globales_restantes,
            'equipo'            => $equipo,
            'nivel'             => $nivel,
            'progreso_fase'     => $numeroPreguntaNivel . ' / ' . $nivel->cantidad_preguntas,
            'pregunta'          => $pregunta,
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