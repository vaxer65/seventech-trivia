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

                if (!in_array($configLock->estado_juego, ['registro', 'en_curso'], true)) {
                    return ['error' => 'El registro de equipos está cerrado en este momento.', 'status' => 403];
                }

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
        $equipo = $token
            ? Equipo::query()
                ->select('equipos.*')
                ->selectRaw('(SELECT COUNT(*) FROM equipos) AS total_equipos_calc')
                ->selectRaw(
                    '(SELECT COUNT(*) + 1 FROM equipos AS o WHERE ' .
                    'o.puntaje_total > equipos.puntaje_total ' .
                    'OR (o.puntaje_total = equipos.puntaje_total AND o.aciertos_totales > equipos.aciertos_totales) ' .
                    'OR (o.puntaje_total = equipos.puntaje_total AND o.aciertos_totales = equipos.aciertos_totales AND o.tiempo_acumulado_ms < equipos.tiempo_acumulado_ms) ' .
                    'OR (o.puntaje_total = equipos.puntaje_total AND o.aciertos_totales = equipos.aciertos_totales AND o.tiempo_acumulado_ms = equipos.tiempo_acumulado_ms AND o.id < equipos.id)) AS posicion_actual_calc'
                )
                ->where('token_sesion', $token)
                ->first()
            : null;

        if (!$equipo) {
            return response()->json(['autenticado' => false]);
        }

        $rankingInfo = [
            'posicion_actual' => max(1, (int) $equipo->posicion_actual_calc),
            'total_equipos'   => max(1, (int) $equipo->total_equipos_calc),
        ];
        $equipo->offsetUnset('posicion_actual_calc');
        $equipo->offsetUnset('total_equipos_calc');

        $config = ConfiguracionEvento::first();
        $this->cerrarSiVencioRelojGlobal($config);

        // Si el evento está en espera, pausado o finalizado
        if (in_array($config->estado_juego, ['registro', 'pausado', 'finalizado'])) {
            return response()->json([
                'autenticado'       => true,
                'estado_juego'      => $config->estado_juego,
                'segundos_globales' => $config->segundos_globales_restantes,
                'equipo'            => $equipo,
                'posicion_actual'   => $rankingInfo['posicion_actual'],
                'total_equipos'     => $rankingInfo['total_equipos'],
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
                'posicion_actual'   => $rankingInfo['posicion_actual'],
                'total_equipos'     => $rankingInfo['total_equipos'],
            ]);
        }

        $asignacion = $this->asignacionActual($equipo, $config);

        if ($asignacion === null || $asignacion['completo']) {
            // Solo en carrera normal se marca la meta, verificando bajo lock que no hubo reinicio en vuelo
            if ($asignacion !== null && $config->estado_juego === 'en_curso' && !$equipo->finalizo_carrera) {
                $abortado = DB::transaction(function () use ($equipo, $config) {
                    $configLock = ConfiguracionEvento::lockForUpdate()->first();
                    $equipoLock = Equipo::whereKey($equipo->id)->lockForUpdate()->first();
                    if (!$equipoLock) {
                        return 'no_autenticado';
                    }
                    if (!$configLock || $configLock->estado_juego !== 'en_curso' || $configLock->semilla_partida !== $config->semilla_partida) {
                        return 'reiniciado';
                    }
                    $equipoLock->update(['finalizo_carrera' => true]);
                    return null;
                });

                if ($abortado === 'no_autenticado') {
                    return response()->json(['autenticado' => false]);
                }
                if ($abortado === 'reiniciado') {
                    $configActual = ConfiguracionEvento::first();
                    return response()->json([
                        'autenticado'       => true,
                        'estado_juego'      => $configActual->estado_juego,
                        'segundos_globales' => $configActual->segundos_globales_restantes,
                        'equipo'            => $equipo->fresh(),
                        'pregunta'          => null,
                    ]);
                }
                $equipo->refresh();
            }
            return $this->respuestaCarreraCompletada($equipo, $config, $rankingInfo);
        }

        $nivel = $asignacion['nivel'];
        $pregunta = $asignacion['pregunta'];
        if (!$pregunta) {
            return $this->respuestaCarreraCompletada($equipo, $config, $rankingInfo);
        }

        $necesitaActualizarFase = ($config->estado_juego === 'en_curso' && $equipo->fase_actual !== $nivel->orden_dificultad);
        $entregaYaExiste = (bool) $pregunta->getRawOriginal('ya_entregada');
        $pregunta->offsetUnset('ya_entregada');

        // Solo abrimos transacción con lock si debemos escribir (actualizar fase_actual o insertar primera entrega)
        if ($necesitaActualizarFase || !$entregaYaExiste) {
            $abortado = DB::transaction(function () use ($equipo, $config, $nivel, $pregunta, $necesitaActualizarFase) {
                $configLock = ConfiguracionEvento::lockForUpdate()->first();
                if (!$configLock
                    || !in_array($configLock->estado_juego, ['en_curso', 'desempate'], true)
                    || $configLock->semilla_partida !== $config->semilla_partida) {
                    return 'reiniciado';
                }

                if ($necesitaActualizarFase) {
                    $actualizados = Equipo::whereKey($equipo->id)->update(['fase_actual' => $nivel->orden_dificultad]);
                    if ($actualizados === 0) {
                        return 'no_autenticado';
                    }
                    $equipo->fase_actual = $nivel->orden_dificultad;
                }

                try {
                    Entrega::insertOrIgnore([
                        'equipo_id'    => $equipo->id,
                        'pregunta_id'  => $pregunta->id,
                        'entregada_at' => now()->format('Y-m-d H:i:s.v'),
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    return 'no_autenticado';
                }

                return null;
            });

            if ($abortado === 'no_autenticado') {
                return response()->json(['autenticado' => false]);
            }
            if ($abortado === 'reiniciado') {
                $configActual = ConfiguracionEvento::first();
                $equipoActual = Equipo::find($equipo->id);
                if (!$equipoActual) {
                    return response()->json(['autenticado' => false]);
                }
                return response()->json([
                    'autenticado'       => true,
                    'estado_juego'      => $configActual->estado_juego,
                    'segundos_globales' => $configActual->segundos_globales_restantes,
                    'equipo'            => $equipoActual,
                    'pregunta'          => null,
                ]);
            }
        }

        // Barajar opciones A/B/C/D de forma determinista y opaca en el servidor por (equipo_id, pregunta_id, semilla_partida)
        $this->barajarOpcionesParaEquipo($pregunta, (int) $equipo->id, (string) $config->semilla_partida);

        return response()->json([
            'autenticado'       => true,
            'estado_juego'      => $config->estado_juego,
            'segundos_globales' => $config->segundos_globales_restantes,
            'equipo'            => $equipo,
            'posicion_actual'   => $rankingInfo['posicion_actual'],
            'total_equipos'     => $rankingInfo['total_equipos'],
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

        if (!in_array($config->estado_juego, ['en_curso', 'desempate'], true)
            || ($config->estado_juego === 'desempate' && !$equipo->en_desempate)) {
            return $this->respuestaYaRespondidaOResync($equipo, $preguntaId, 'La ronda no está activa.');
        }

        $opcionRaw = strtolower(trim((string) $request->opcion_elegida));
        $opcion = in_array($opcionRaw, ['a', 'b', 'c', 'd'], true) ? $opcionRaw : null;

        // T5 + Blindaje Bloque 1: verificación y registro dentro de una sola transacción con orden estricto de locks
        try {
            $resultado = DB::transaction(function () use ($equipo, $preguntaId, $opcion) {
                // 1. Lock sobre ConfiguracionEvento primero, luego sobre Equipo
                $configLock = ConfiguracionEvento::lockForUpdate()->first();
                $equipoLock = Equipo::whereKey($equipo->id)->lockForUpdate()->first();

                if (!$equipoLock) {
                    return ['status' => 401, 'body' => ['error' => 'Sesión no válida']];
                }

                $this->cerrarSiVencioRelojGlobal($configLock);

                if (!in_array($configLock->estado_juego, ['en_curso', 'desempate'], true)
                    || ($configLock->estado_juego === 'desempate' && !$equipoLock->en_desempate)) {
                    return ['resync_msg' => 'La ronda no está activa.', 'equipo' => $equipoLock];
                }

                // T1: solo se acepta la pregunta asignada bajo la semilla_partida vigente
                $asignacion = $this->asignacionActual($equipoLock, $configLock);
                $pregunta = $asignacion['pregunta'] ?? null;

                if (!$pregunta || $pregunta->id !== $preguntaId) {
                    return ['resync_msg' => 'Esa pregunta ya no está vigente.', 'equipo' => $equipoLock];
                }

                // T2: la entrega debe existir en la partida actual y el tiempo se mide en el servidor
                $entrega = Entrega::where('equipo_id', $equipoLock->id)->where('pregunta_id', $pregunta->id)->first();
                if (!$entrega) {
                    return ['status' => 409, 'body' => ['error' => 'Entrega no registrada.', 'resync' => true]];
                }

                $nivel = $asignacion['nivel'];
                $limiteMs = max(1, (int) $nivel->tiempo_limite_seg) * 1000;
                $transcurridoMs = max(0, now()->getTimestampMs() - $entrega->entregada_at->getTimestampMs());
                $fueraDeTiempo = $transcurridoMs > ($limiteMs + self::GRACIA_RED_MS);
                $tiempoMs = min($transcurridoMs, $limiteMs);

                // Obtener respuesta_correcta real de la BD (ignorando $hidden) y traducir la letra visual enviada por el equipo
                $respuestaCorrectaReal = strtolower(trim((string) $pregunta->getRawOriginal('respuesta_correcta')));
                $claveOriginalElegida = $opcion !== null
                    ? $this->traducirOpcionVisualAOriginal((int) $pregunta->id, (int) $equipoLock->id, (string) $configLock->semilla_partida, $opcion)
                    : null;
                $esCorrecta = (!$fueraDeTiempo && $claveOriginalElegida !== null && $claveOriginalElegida === $respuestaCorrectaReal);
                $puntosGanados = $esCorrecta ? (int) $nivel->puntos_por_acierto : 0;

                RespuestaEquipo::create([
                    'equipo_id'           => $equipoLock->id,
                    'pregunta_id'         => $pregunta->id,
                    'nivel_id'            => $pregunta->nivel_id,
                    'opcion_elegida'      => $claveOriginalElegida,
                    'es_correcta'         => $esCorrecta,
                    'puntos_ganados'      => $puntosGanados,
                    'tiempo_respuesta_ms' => $tiempoMs,
                ]);

                $rachaAnterior = (int) $equipoLock->racha_actual;
                $rachaMaxAnterior = (int) $equipoLock->racha_maxima;
                $nuevaRacha = $esCorrecta ? ($rachaAnterior + 1) : 0;
                $nuevaRachaMax = max($rachaMaxAnterior, $nuevaRacha);
                $rachaPerdida = ($rachaAnterior >= 3 && $nuevaRacha === 0);

                // El equipo SIEMPRE avanza en preguntas_contestadas aunque falle
                Equipo::whereKey($equipoLock->id)->update([
                    'preguntas_contestadas' => DB::raw('preguntas_contestadas + 1'),
                    'tiempo_acumulado_ms'   => DB::raw('tiempo_acumulado_ms + ' . (int) $tiempoMs),
                    'aciertos_totales'      => DB::raw('aciertos_totales + ' . ($esCorrecta ? 1 : 0)),
                    'puntaje_total'         => DB::raw('puntaje_total + ' . (int) $puntosGanados),
                    'racha_actual'          => $nuevaRacha,
                    'racha_maxima'          => $nuevaRachaMax,
                ]);

                return [
                    'status' => 200,
                    'body'   => [
                        'procesado'       => true,
                        'es_correcta'     => $esCorrecta,
                        'fuera_de_tiempo' => $fueraDeTiempo,
                        'puntos_sumados'  => $puntosGanados,
                        'puntaje_total'   => $equipoLock->fresh()->puntaje_total,
                        'racha_actual'    => $nuevaRacha,
                        'racha_maxima'    => $nuevaRachaMax,
                        'racha_perdida'   => $rachaPerdida,
                        'mensaje'         => 'Respuesta registrada.',
                    ],
                ];
            });
        } catch (UniqueConstraintViolationException $e) {
            return $this->respuestaDuplicada($equipo);
        }

        if (isset($resultado['resync_msg'])) {
            return $this->respuestaYaRespondidaOResync($resultado['equipo'], $preguntaId, $resultado['resync_msg']);
        }

        return response()->json($resultado['body'], $resultado['status']);
    }

    // Registrar incidente de salida de pestaña/app y anular la pregunta en curso con 0 puntos
    public function reportarSalidaPantalla(Request $request)
    {
        $token = $request->session()->get('equipo_token');
        $equipo = $token ? Equipo::where('token_sesion', $token)->first() : null;

        if (!$equipo) {
            return response()->json(['error' => 'Sesión no válida'], 401);
        }

        $request->validate([
            'pregunta_id' => 'required|integer',
        ]);
        $preguntaId = (int) $request->input('pregunta_id');

        $config = ConfiguracionEvento::first();
        $this->cerrarSiVencioRelojGlobal($config);

        if (!in_array($config->estado_juego, ['en_curso', 'desempate'], true)
            || ($config->estado_juego === 'desempate' && !$equipo->en_desempate)) {
            return response()->json(['error' => 'La ronda no está activa.', 'resync' => true], 409);
        }

        try {
            $resultado = DB::transaction(function () use ($equipo, $preguntaId) {
                // Orden estricto de locks: primero ConfiguracionEvento, luego Equipo
                $configLock = ConfiguracionEvento::lockForUpdate()->first();
                $equipoLock = Equipo::whereKey($equipo->id)->lockForUpdate()->first();

                if (!$equipoLock) {
                    return ['status' => 401, 'body' => ['error' => 'Sesión no válida']];
                }

                $this->cerrarSiVencioRelojGlobal($configLock);

                if (!in_array($configLock->estado_juego, ['en_curso', 'desempate'], true)
                    || ($configLock->estado_juego === 'desempate' && !$equipoLock->en_desempate)) {
                    return ['status' => 409, 'body' => ['error' => 'La ronda no está activa.', 'resync' => true]];
                }

                $asignacion = $this->asignacionActual($equipoLock, $configLock);
                $pregunta = $asignacion['pregunta'] ?? null;

                if (!$pregunta || $pregunta->id !== $preguntaId) {
                    $yaRegistrada = RespuestaEquipo::where('equipo_id', $equipoLock->id)
                        ->where('pregunta_id', $preguntaId)
                        ->exists();

                    if ($yaRegistrada) {
                        return [
                            'status' => 200,
                            'body'   => [
                                'bloqueada'        => true,
                                'duplicada'        => true,
                                'puntos_sumados'   => 0,
                                'salidas_pantalla' => (int) $equipoLock->salidas_pantalla,
                                'mensaje'          => 'Incidente ya registrado.',
                            ],
                        ];
                    }

                    return ['status' => 409, 'body' => ['error' => 'Esa pregunta ya no está vigente.', 'resync' => true]];
                }

                $entrega = Entrega::where('equipo_id', $equipoLock->id)
                    ->where('pregunta_id', $pregunta->id)
                    ->first();

                if (!$entrega) {
                    return ['status' => 409, 'body' => ['error' => 'Entrega no registrada.', 'resync' => true]];
                }

                $nivel = $asignacion['nivel'];
                $limiteMs = max(1, (int) $nivel->tiempo_limite_seg) * 1000;
                $transcurridoMs = max(0, now()->getTimestampMs() - $entrega->entregada_at->getTimestampMs());
                $tiempoMs = min($transcurridoMs, $limiteMs);

                RespuestaEquipo::create([
                    'equipo_id'            => $equipoLock->id,
                    'pregunta_id'          => $pregunta->id,
                    'nivel_id'             => $pregunta->nivel_id,
                    'opcion_elegida'       => null,
                    'es_correcta'          => false,
                    'bloqueada_por_salida' => true,
                    'puntos_ganados'       => 0,
                    'tiempo_respuesta_ms'  => $tiempoMs,
                ]);

                $rachaAnterior = (int) $equipoLock->racha_actual;
                $rachaMaxAnterior = (int) $equipoLock->racha_maxima;
                $rachaPerdida = ($rachaAnterior >= 3);

                Equipo::whereKey($equipoLock->id)->update([
                    'preguntas_contestadas' => DB::raw('preguntas_contestadas + 1'),
                    'tiempo_acumulado_ms'   => DB::raw('tiempo_acumulado_ms + ' . (int) $tiempoMs),
                    'salidas_pantalla'      => DB::raw('salidas_pantalla + 1'),
                    'racha_actual'          => 0,
                ]);

                return [
                    'status' => 200,
                    'body'   => [
                        'bloqueada'        => true,
                        'duplicada'        => false,
                        'puntos_sumados'   => 0,
                        'salidas_pantalla' => (int) $equipoLock->salidas_pantalla + 1,
                        'racha_actual'     => 0,
                        'racha_maxima'     => $rachaMaxAnterior,
                        'racha_perdida'    => $rachaPerdida,
                        'mensaje'          => 'Pregunta anulada con 0 puntos por salir de la pantalla de competencia.',
                    ],
                ];
            });
        } catch (UniqueConstraintViolationException $e) {
            return response()->json([
                'bloqueada'        => true,
                'duplicada'        => true,
                'puntos_sumados'   => 0,
                'salidas_pantalla' => (int) ($equipo->fresh()->salidas_pantalla ?? 0),
                'mensaje'          => 'Incidente ya registrado.',
            ]);
        }

        return response()->json($resultado['body'], $resultado['status']);
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

        $estadisticasPorNivel = RespuestaEquipo::where('respuestas_equipos.equipo_id', $equipo->id)
            ->join('preguntas', 'respuestas_equipos.pregunta_id', '=', 'preguntas.id')
            ->selectRaw('respuestas_equipos.nivel_id, COUNT(*) as total, SUM(CASE WHEN preguntas.es_amena = 1 THEN 1 ELSE 0 END) as amenas')
            ->groupBy('respuestas_equipos.nivel_id')
            ->get()
            ->keyBy('nivel_id');

        foreach ($niveles as $nivel) {
            $fila = $estadisticasPorNivel->get($nivel->id);
            $contestadas = (int) ($fila->total ?? 0);
            $amenasContestadas = (int) ($fila->amenas ?? 0);
            if ($contestadas < $nivel->cantidad_preguntas) {
                return [
                    'completo'    => false,
                    'nivel'       => $nivel,
                    'contestadas' => $contestadas,
                    'pregunta'    => $this->siguientePregunta($equipo, $nivel, $config, $contestadas, $amenasContestadas),
                ];
            }
        }

        return ['completo' => true, 'nivel' => null, 'contestadas' => 0, 'pregunta' => null];
    }

    // Orden pseudoaleatorio por partida, IDÉNTICO para todos los equipos (justicia deportiva)
    // En etapas normales garantiza exactamente 1 pregunta amena/curiosa y el resto técnicas.
    private function siguientePregunta(Equipo $equipo, Nivel $nivel, ConfiguracionEvento $config, int $contestadas = 0, int $amenasContestadas = 0): ?Pregunta
    {
        if (empty($config->semilla_partida) || $config->semilla_partida === 'seventech_init') {
            $config->update(['semilla_partida' => Str::random(16)]);
        }

        $semilla = (string) $config->semilla_partida;

        $prefiereAmena = 0;
        if (!$nivel->es_desempate && $nivel->cantidad_preguntas >= 2) {
            $turnoAmena = hexdec(substr(hash('sha256', "amena:{$nivel->id}:{$semilla}"), 0, 8)) % $nivel->cantidad_preguntas;
            if ($amenasContestadas === 0 && ($contestadas === $turnoAmena || $contestadas >= $nivel->cantidad_preguntas - 1)) {
                $prefiereAmena = 1;
            }
        }

        $query = Pregunta::select('preguntas.*')
            ->selectRaw('EXISTS(SELECT 1 FROM entregas WHERE entregas.equipo_id = ? AND entregas.pregunta_id = preguntas.id) as ya_entregada', [$equipo->id])
            ->where('nivel_id', $nivel->id)
            ->where('activa', true)
            ->whereNotExists(function ($sub) use ($equipo) {
                $sub->select(DB::raw(1))
                    ->from('respuestas_equipos')
                    ->whereColumn('respuestas_equipos.pregunta_id', 'preguntas.id')
                    ->where('respuestas_equipos.equipo_id', $equipo->id);
            });

        if ($config->modulo_activo_id) {
            $query->where('modulo_id', $config->modulo_activo_id);
        }

        return $query
            ->orderByRaw('CASE WHEN preguntas.es_amena = ? THEN 0 ELSE 1 END ASC', [$prefiereAmena])
            ->orderByRaw('CRC32(SHA2(CONCAT(preguntas.id, "-", ?), 256))', [$semilla])
            ->orderBy('preguntas.id')
            ->first();
    }

    // 24 permutaciones canónicas (S4) de ['a', 'b', 'c', 'd']; cada letra aparece exactamente 6 veces (25%) en cada posición
    public const PERMUTACIONES_ABCD = [
        ['a', 'b', 'c', 'd'], ['a', 'b', 'd', 'c'], ['a', 'c', 'b', 'd'], ['a', 'c', 'd', 'b'], ['a', 'd', 'b', 'c'], ['a', 'd', 'c', 'b'],
        ['b', 'a', 'c', 'd'], ['b', 'a', 'd', 'c'], ['b', 'c', 'a', 'd'], ['b', 'c', 'd', 'a'], ['b', 'd', 'a', 'c'], ['b', 'd', 'c', 'a'],
        ['c', 'a', 'b', 'd'], ['c', 'a', 'd', 'b'], ['c', 'b', 'a', 'd'], ['c', 'b', 'd', 'a'], ['c', 'd', 'a', 'b'], ['c', 'd', 'b', 'a'],
        ['d', 'a', 'b', 'c'], ['d', 'a', 'c', 'b'], ['d', 'b', 'a', 'c'], ['d', 'b', 'c', 'a'], ['d', 'c', 'a', 'b'], ['d', 'c', 'b', 'a'],
    ];

    public static function obtenerPermutacionOpciones(int $preguntaId, int $equipoId, string $semilla): array
    {
        $hashHex = substr(hash('sha256', "{$equipoId}:{$preguntaId}:{$semilla}"), 0, 8);
        $indice = hexdec($hashHex) % 24;
        return self::PERMUTACIONES_ABCD[$indice];
    }

    public function barajarOpcionesParaEquipo(Pregunta $pregunta, int $equipoId, string $semilla): array
    {
        $perm = self::obtenerPermutacionOpciones((int) $pregunta->id, $equipoId, $semilla);
        $originales = [
            'a' => $pregunta->getRawOriginal('opcion_a') ?? $pregunta->opcion_a,
            'b' => $pregunta->getRawOriginal('opcion_b') ?? $pregunta->opcion_b,
            'c' => $pregunta->getRawOriginal('opcion_c') ?? $pregunta->opcion_c,
            'd' => $pregunta->getRawOriginal('opcion_d') ?? $pregunta->opcion_d,
        ];

        $pregunta->opcion_a = $originales[$perm[0]];
        $pregunta->opcion_b = $originales[$perm[1]];
        $pregunta->opcion_c = $originales[$perm[2]];
        $pregunta->opcion_d = $originales[$perm[3]];

        return $perm;
    }

    public function traducirOpcionVisualAOriginal(int $preguntaId, int $equipoId, string $semilla, string $opcionVisual): ?string
    {
        $mapaIndice = ['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3];
        $letra = strtolower(trim($opcionVisual));
        if (!isset($mapaIndice[$letra])) {
            return null;
        }
        $perm = self::obtenerPermutacionOpciones($preguntaId, $equipoId, $semilla);
        return $perm[$mapaIndice[$letra]];
    }

    private function respuestaCarreraCompletada(Equipo $equipo, ConfiguracionEvento $config, array $rankingInfo = [])
    {
        return response()->json([
            'autenticado'       => true,
            'estado_juego'      => 'carrera_completada',
            'segundos_globales' => $config->segundos_globales_restantes,
            'equipo'            => $equipo,
            'posicion_actual'   => $rankingInfo['posicion_actual'] ?? null,
            'total_equipos'     => $rankingInfo['total_equipos'] ?? null,
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