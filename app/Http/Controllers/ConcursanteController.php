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
use App\Models\Entrega;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

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

        $coloresPista = ['#00E5FF', '#10B981', '#F59E0B', '#38BDF8', '#A855F7', '#EC4899', '#22D3EE'];
        $token = Str::random(60);
        $pin = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

        // C5: Transacción atómica con lock sobre configuración para evitar carreras de cupo, nombres o equipos a medias
        try {
            $resultado = DB::transaction(function () use ($request, $integrantesLimpios, $coloresPista, $token, $pin) {
                $configLock = ConfiguracionEvento::lockForUpdate()->first();
                $totalActual = Equipo::count();

                if ($totalActual >= $configLock->max_equipos_permitidos) {
                    return ['error' => 'Se alcanzó el límite máximo de equipos permitidos.', 'status' => 403];
                }

                foreach ($integrantesLimpios as $nombreReal) {
                    $yaExiste = Integrante::whereRaw('LOWER(nombre_completo) = ?', [mb_strtolower($nombreReal, 'UTF-8')])->exists();
                    if ($yaExiste) {
                        return [
                            'error'  => 'El participante "' . $nombreReal . '" ya se encuentra registrado en otro equipo.',
                            'status' => 422,
                        ];
                    }
                }

                $equipo = Equipo::create([
                    'nombre_equipo'    => $request->nombre_equipo,
                    'token_sesion'     => $token,
                    'pin_recuperacion' => $pin,
                    'color_carril'     => $coloresPista[$totalActual % count($coloresPista)],
                    'fase_actual'      => 1,
                ]);

                foreach ($integrantesLimpios as $nombreReal) {
                    Integrante::create([
                        'equipo_id'       => $equipo->id,
                        'nombre_completo' => ucwords(mb_strtolower($nombreReal, 'UTF-8')),
                    ]);
                }

                return ['equipo' => $equipo];
            });
        } catch (UniqueConstraintViolationException $e) {
            return response()->json([
                'error' => '¡Ese nombre de equipo ya está en uso! Elige otro.',
            ], 422);
        }

        if (isset($resultado['error'])) {
            return response()->json(['error' => $resultado['error']], $resultado['status']);
        }

        $equipo = $resultado['equipo'];

        $request->session()->regenerate();
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
        $this->cerrarSiVencioRelojGlobal($config);

        // Si el evento está en espera, pausado o finalizado
        if (in_array($config->estado_juego, ['registro', 'pausado', 'finalizado'])) {
            return response()->json([
                'autenticado'       => true,
                'estado_juego'      => $config->estado_juego,
                'segundos_globales' => $config->segundos_globales_restantes,
                'equipo'            => $equipo,
                'pregunta'          => null,
            ]);
        }

        // Ronda de desempate para equipos que no participan en ella
        if ($config->estado_juego === 'desempate' && !$equipo->en_desempate) {
            return response()->json([
                'autenticado'       => true,
                'estado_juego'      => 'esperando_desempate',
                'segundos_globales' => null,
                'equipo'            => $equipo,
            ]);
        }

        $asignacion = $this->asignacionActual($equipo, $config);

        if ($asignacion === null || $asignacion['completo']) {
            // Solo en carrera normal se marca la meta (igual que antes)
            if ($asignacion !== null && $config->estado_juego === 'en_curso' && !$equipo->finalizo_carrera) {
                $equipo->update(['finalizo_carrera' => true]);
            }
            return $this->respuestaCarreraCompletada($equipo, $config);
        }

        $nivel = $asignacion['nivel'];
        if ($config->estado_juego === 'en_curso' && $equipo->fase_actual !== $nivel->orden_dificultad) {
            $equipo->update(['fase_actual' => $nivel->orden_dificultad]);
        }

        $pregunta = $asignacion['pregunta'];
        if (!$pregunta) {
            return $this->respuestaCarreraCompletada($equipo, $config);
        }

        // Registrar la entrega SOLO la primera vez (recargar no reinicia el cronómetro)
        Entrega::insertOrIgnore([
            'equipo_id'    => $equipo->id,
            'pregunta_id'  => $pregunta->id,
            'entregada_at' => now()->format('Y-m-d H:i:s.v'),
        ]);

        return response()->json([
            'autenticado'       => true,
            'estado_juego'      => $config->estado_juego,
            'segundos_globales' => $config->segundos_globales_restantes,
            'equipo'            => $equipo,
            'nivel'             => $nivel,
            'progreso_fase'     => ($asignacion['contestadas'] + 1) . ' / ' . $nivel->cantidad_preguntas,
            'pregunta'          => $pregunta,
        ]);
    }

    // Recibir respuesta, calificar en secreto y avanzar SIEMPRE a la siguiente pregunta.
    // Todo (pregunta vigente, tiempo, puntaje) se decide en el servidor.
    public function enviarRespuesta(Request $request)
    {
        $token = $request->session()->get('equipo_token');
        $equipo = $token ? Equipo::where('token_sesion', $token)->first() : null;

        if (!$equipo) {
            return response()->json(['error' => 'Sesión no válida'], 401);
        }

        $request->validate([
            'pregunta_id'    => 'required|integer',
            'opcion_elegida' => 'nullable|string|max:20',
        ]);
        $preguntaId = (int) $request->input('pregunta_id');

        // T3: solo se responde con la ronda activa y el reloj global vigente
        $config = ConfiguracionEvento::first();
        $this->cerrarSiVencioRelojGlobal($config);

        if (!in_array($config->estado_juego, ['en_curso', 'desempate'])
            || ($config->estado_juego === 'desempate' && !$equipo->en_desempate)) {
            return $this->respuestaYaRespondidaOResync($equipo, $preguntaId, 'La ronda no está activa.');
        }

        // T1: solo se acepta la pregunta que el servidor tiene asignada a este equipo
        $asignacion = $this->asignacionActual($equipo, $config);
        $pregunta = $asignacion['pregunta'] ?? null;

        if (!$pregunta || $pregunta->id !== $preguntaId) {
            return $this->respuestaYaRespondidaOResync($equipo, $preguntaId, 'Esa pregunta ya no está vigente.');
        }

        // T2: el tiempo se calcula desde la entrega registrada en el servidor
        $entrega = Entrega::where('equipo_id', $equipo->id)->where('pregunta_id', $pregunta->id)->first();
        if (!$entrega) {
            return response()->json(['error' => 'Entrega no registrada.', 'resync' => true], 409);
        }

        $nivel = $asignacion['nivel'];
        $limiteMs = max(1, (int) $nivel->tiempo_limite_seg) * 1000;
        $transcurridoMs = max(0, now()->getTimestampMs() - $entrega->entregada_at->getTimestampMs());
        $fueraDeTiempo = $transcurridoMs > ($limiteMs + self::GRACIA_RED_MS);
        $tiempoMs = min($transcurridoMs, $limiteMs);

        $opcionRaw = strtolower(trim((string) $request->opcion_elegida));
        $opcion = in_array($opcionRaw, ['a', 'b', 'c', 'd']) ? $opcionRaw : null;

        // Obtener respuesta_correcta real de la BD (ignorando $hidden)
        $respuestaCorrectaReal = strtolower(trim((string) $pregunta->getRawOriginal('respuesta_correcta')));
        $esCorrecta = (!$fueraDeTiempo && $opcion !== null && $opcion === $respuestaCorrectaReal);

        $puntosGanados = $esCorrecta ? (int) $nivel->puntos_por_acierto : 0;

        // T5: registro + acumulados en una sola transacción; un duplicado concurrente no da 500
        try {
            DB::transaction(function () use ($equipo, $pregunta, $opcion, $esCorrecta, $puntosGanados, $tiempoMs) {
                RespuestaEquipo::create([
                    'equipo_id'           => $equipo->id,
                    'pregunta_id'         => $pregunta->id,
                    'nivel_id'            => $pregunta->nivel_id,
                    'opcion_elegida'      => $opcion,
                    'es_correcta'         => $esCorrecta,
                    'puntos_ganados'      => $puntosGanados,
                    'tiempo_respuesta_ms' => $tiempoMs,
                ]);

                // El equipo SIEMPRE avanza en preguntas_contestadas aunque falle
                Equipo::whereKey($equipo->id)->update([
                    'preguntas_contestadas' => DB::raw('preguntas_contestadas + 1'),
                    'tiempo_acumulado_ms'   => DB::raw('tiempo_acumulado_ms + ' . (int) $tiempoMs),
                    'aciertos_totales'      => DB::raw('aciertos_totales + ' . ($esCorrecta ? 1 : 0)),
                    'puntaje_total'         => DB::raw('puntaje_total + ' . (int) $puntosGanados),
                ]);
            });
        } catch (UniqueConstraintViolationException $e) {
            return $this->respuestaDuplicada($equipo);
        }

        return response()->json([
            'procesado'       => true,
            'es_correcta'     => $esCorrecta,
            'fuera_de_tiempo' => $fueraDeTiempo,
            'puntos_sumados'  => $puntosGanados,
            'puntaje_total'   => $equipo->fresh()->puntaje_total,
            'mensaje'         => 'Respuesta registrada.',
        ]);
    }

    // Margen para la latencia de red cuando el celular envía justo al agotarse el tiempo
    private const GRACIA_RED_MS = 1500;

    // Cierre perezoso del evento cuando se agota el reloj global
    private function cerrarSiVencioRelojGlobal(ConfiguracionEvento $config): void
    {
        if ($config->estado_juego === 'en_curso' && $config->fin_evento_at && now()->greaterThanOrEqualTo($config->fin_evento_at)) {
            $config->update(['estado_juego' => 'finalizado']);
        }
    }

    /**
     * Única fuente de verdad de "qué pregunta le toca al equipo".
     * La usan tanto la entrega (GET) como la calificación (POST), así ambas ven el mismo orden.
     * Devuelve null si la ronda no está activa para el equipo.
     */
    private function asignacionActual(Equipo $equipo, ConfiguracionEvento $config): ?array
    {
        if ($config->estado_juego === 'desempate') {
            if (!$equipo->en_desempate) {
                return null;
            }
            $niveles = Nivel::where('es_desempate', true)->limit(1)->get();
        } elseif ($config->estado_juego === 'en_curso') {
            $niveles = Nivel::where('es_desempate', false)->orderBy('orden_dificultad', 'asc')->get();
        } else {
            return null;
        }

        $conteo = RespuestaEquipo::where('equipo_id', $equipo->id)
            ->selectRaw('nivel_id, COUNT(*) as total')
            ->groupBy('nivel_id')
            ->pluck('total', 'nivel_id');

        foreach ($niveles as $nivel) {
            $contestadas = (int) ($conteo[$nivel->id] ?? 0);
            if ($contestadas < $nivel->cantidad_preguntas) {
                return [
                    'completo'    => false,
                    'nivel'       => $nivel,
                    'contestadas' => $contestadas,
                    'pregunta'    => $this->siguientePregunta($equipo, $nivel, $config),
                ];
            }
        }

        return ['completo' => true, 'nivel' => null, 'contestadas' => 0, 'pregunta' => null];
    }

    // Orden pseudoaleatorio por partida, IDÉNTICO para todos los equipos (justicia deportiva)
    private function siguientePregunta(Equipo $equipo, Nivel $nivel, ConfiguracionEvento $config): ?Pregunta
    {
        $respondidasIds = RespuestaEquipo::where('equipo_id', $equipo->id)->pluck('pregunta_id');

        $query = Pregunta::where('nivel_id', $nivel->id)
            ->where('activa', true)
            ->whereNotIn('id', $respondidasIds);

        if ($config->modulo_activo_id) {
            $query->where('modulo_id', $config->modulo_activo_id);
        }

        if (empty($config->semilla_partida) || $config->semilla_partida === 'seventech_init') {
            $config->update(['semilla_partida' => Str::random(16)]);
        }

        $semilla = (string) $config->semilla_partida;

        return $query->orderByRaw('CRC32(CONCAT(id, ?))', [$semilla])->orderBy('id')->first();
    }

    private function respuestaCarreraCompletada(Equipo $equipo, ConfiguracionEvento $config)
    {
        return response()->json([
            'autenticado'       => true,
            'estado_juego'      => 'carrera_completada',
            'segundos_globales' => $config->segundos_globales_restantes,
            'equipo'            => $equipo,
        ]);
    }

    // Reenvío de una respuesta ya registrada (doble toque / reintento de red): 200 sin sumar nada
    private function respuestaYaRespondidaOResync(Equipo $equipo, int $preguntaId, string $mensaje)
    {
        if (RespuestaEquipo::where('equipo_id', $equipo->id)->where('pregunta_id', $preguntaId)->exists()) {
            return $this->respuestaDuplicada($equipo);
        }
        return response()->json(['error' => $mensaje, 'resync' => true], 409);
    }

    private function respuestaDuplicada(Equipo $equipo)
    {
        return response()->json([
            'procesado'      => true,
            'duplicada'      => true,
            'es_correcta'    => false,
            'puntos_sumados' => 0,
            'puntaje_total'  => $equipo->fresh()->puntaje_total,
        ]);
    }

    // Permitir cerrar sesión del equipo actual y regresar al inicio/registro
    public function salirEquipo(Request $request)
    {
        $request->session()->forget('equipo_token');
        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }
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

        $request->session()->regenerate();
        $request->session()->put('equipo_token', $equipo->token_sesion);

        return response()->json([
            'mensaje' => '¡Sesión de equipo recuperada con éxito!',
            'equipo'  => $equipo->load('integrantes')
        ]);
    }
}