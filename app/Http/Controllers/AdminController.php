<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConfiguracionEvento;
use App\Models\Modulo;
use App\Models\Nivel;
use App\Models\Pregunta;
use App\Models\Equipo;
use App\Models\Integrante;
use App\Models\RespuestaEquipo;
use App\Models\Entrega;
use App\Models\HistorialEvento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
        $estimacionDuracion = self::calcularEstimacionDuracion($niveles, (int) ($config?->tiempo_total_minutos ?? 15));

        return view('admin.index', compact('autorizado', 'config', 'modulos', 'niveles', 'preguntas', 'equipos', 'historial', 'estimacionDuracion'));
    }

    /**
     * Calcula la duración estimada del torneo (ritmo promedio, máximo teórico, recomendado y semáforo).
     */
    public static function calcularEstimacionDuracion($niveles, int $tiempoTotalMinutos): array
    {
        $transicionSeg = 2;
        $desgloseEtapas = [];
        $segPromedio = 0.0;
        $segMaximo = 0;
        $segColchonDesempate = 0;

        foreach ($niveles as $nivel) {
            $preguntas = max(0, (int) $nivel->cantidad_preguntas);
            $segundosLimite = max(0, (int) $nivel->tiempo_limite_seg);

            if ($nivel->es_desempate) {
                $segColchonDesempate = max($segColchonDesempate, 2 * ($segundosLimite + $transicionSeg));
                continue;
            }

            $segPuros = $preguntas * $segundosLimite;
            $segMaxEtapa = $preguntas * ($segundosLimite + $transicionSeg);
            $segPromEtapa = $preguntas * (($segundosLimite * 0.60) + $transicionSeg);

            $segMaximo += $segMaxEtapa;
            $segPromedio += $segPromEtapa;

            $desgloseEtapas[] = [
                'id'               => $nivel->id,
                'nombre'           => $nivel->nombre,
                'preguntas'        => $preguntas,
                'tiempo_limite'    => $segundosLimite,
                'seg_puros'        => $segPuros,
                'seg_max_etapa'    => $segMaxEtapa,
                'seg_prom_etapa'   => (int) round($segPromEtapa),
            ];
        }

        $recomendadoMin = max(1, (int) ceil(($segMaximo + $segColchonDesempate) / 60));
        $tiempoActualSeg = max(0, $tiempoTotalMinutos) * 60;

        if ($tiempoActualSeg < $segPromedio) {
            $semaforo = 'rojo';
            $semaforoTexto = '⚠️ Tiempo insuficiente';
            $semaforoDetalle = 'El reloj global cortará la carrera antes de que un equipo promedio termine.';
        } elseif ($tiempoTotalMinutos < $recomendadoMin) {
            $semaforo = 'ambar';
            $semaforoTexto = '⚡ Tiempo ajustado';
            $semaforoDetalle = 'Cubre el ritmo promedio, pero equipos lentos o un desempate podrían quedar incompletos.';
        } else {
            $semaforo = 'verde';
            $semaforoTexto = '✅ Duración óptima';
            $semaforoDetalle = 'Cubre el máximo teórico de todas las etapas más margen para Muerte Súbita.';
        }

        return [
            'transicion_seg'         => $transicionSeg,
            'desglose_etapas'        => $desgloseEtapas,
            'seg_promedio'           => (int) round($segPromedio),
            'min_promedio'           => round($segPromedio / 60, 1),
            'seg_maximo'             => $segMaximo,
            'min_maximo'             => round($segMaximo / 60, 1),
            'seg_colchon_desempate'  => $segColchonDesempate,
            'recomendado_min'        => $recomendadoMin,
            'semaforo'               => $semaforo,
            'semaforo_texto'         => $semaforoTexto,
            'semaforo_detalle'       => $semaforoDetalle,
        ];
    }

    public function login(Request $request)
    {
        $hash = trim((string) config('app.admin_password_hash'), " \t\n\r\0\x0B'\"");
        $password = (string) $request->input('password');

        $valido = false;
        if ($hash !== '') {
            try {
                $valido = str_starts_with($hash, '$2')
                    ? Hash::check($password, $hash)
                    : hash_equals($hash, $password);
            } catch (\Throwable $e) {
                $valido = false;
            }
        }

        if ($valido) {
            $request->session()->regenerate();
            $request->session()->put('es_admin_seventech', true);
            return redirect('/admin');
        }
        return back()->withErrors(['password' => 'Contraseña de administrador incorrecta.']);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('es_admin_seventech');
        return redirect('/admin');
    }

    private function esPartidaBloqueada(?ConfiguracionEvento $config): bool
    {
        return $config && in_array($config->estado_juego, ['en_curso', 'pausado', 'desempate'], true);
    }

    private function responderBloqueoPartida(Request $request, string $mensaje = 'No se pueden modificar parámetros estructurales, etapas ni preguntas mientras la partida está en curso, pausada o en desempate.')
    {
        if ($request->wantsJson()) {
            return response()->json(['error' => $mensaje], 409);
        }

        abort(409, $mensaje);
    }

    private function responderExitoAdmin(Request $request, string $mensaje, array $extra = [])
    {
        if ($request->wantsJson()) {
            return response()->json(array_merge(['ok' => true, 'mensaje' => $mensaje], $extra));
        }

        return back()->with('ok', $mensaje);
    }

    // Cambiar estado del juego, tiempo global (minutos) o módulo activo
    public function actualizarConfiguracion(Request $request)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        $config = ConfiguracionEvento::first();

        if ($request->has('modulo_activo_id') && $request->input('modulo_activo_id') === '') {
            $request->merge(['modulo_activo_id' => null]);
        }

        $validated = $request->validate([
            'estado_juego'           => 'required|in:registro,en_curso,pausado,desempate,finalizado',
            'modulo_activo_id'       => 'nullable|exists:modulos,id',
            'max_integrantes_equipo' => 'required|integer|between:2,10',
            'max_equipos_permitidos' => 'required|integer|between:1,200',
            'tiempo_total_minutos'   => 'nullable|integer|min:1|max:600',
        ]);

        if ($this->esPartidaBloqueada($config)) {
            if ($request->has('modulo_activo_id')) {
                $solicitadoMod = $validated['modulo_activo_id'] !== null ? (int) $validated['modulo_activo_id'] : null;
                $actualMod = $config->modulo_activo_id !== null ? (int) $config->modulo_activo_id : null;
                if ($solicitadoMod !== $actualMod) {
                    return $this->responderBloqueoPartida($request, 'No puedes cambiar el módulo activo mientras la partida está en curso, pausada o en desempate.');
                }
            }

            if ($request->filled('tiempo_total_minutos')) {
                $solicitadoMin = (int) $validated['tiempo_total_minutos'];
                $actualMin = (int) ($config->tiempo_total_minutos ?? 15);
                if ($solicitadoMin !== $actualMin) {
                    return $this->responderBloqueoPartida($request, 'No puedes cambiar la duración global mientras la partida está en curso, pausada o en desempate.');
                }
            }

            $moduloFinal = $config->modulo_activo_id;
            $minutosTotales = (int) ($config->tiempo_total_minutos ?? 15);
        } else {
            $moduloFinal = $request->has('modulo_activo_id')
                ? ($validated['modulo_activo_id'] !== null ? (int) $validated['modulo_activo_id'] : null)
                : $config->modulo_activo_id;
            $minutosTotales = (int) ($validated['tiempo_total_minutos'] ?? $config->tiempo_total_minutos ?? 15);
        }

        $estadoAnterior = $config->estado_juego;
        $nuevoEstado = $validated['estado_juego'];

        $datosActualizar = [
            'estado_juego'           => $nuevoEstado,
            'modulo_activo_id'       => $moduloFinal,
            'max_integrantes_equipo' => (int) $validated['max_integrantes_equipo'],
            'max_equipos_permitidos' => (int) $validated['max_equipos_permitidos'],
            'tiempo_total_minutos'   => $minutosTotales,
        ];

        // Si el evento pasa a 'en_curso' desde 'registro' o 'finalizado', arrancamos el reloj global y sorteamos preguntas nuevas
        if ($nuevoEstado === 'en_curso' && in_array($estadoAnterior, ['registro', 'finalizado'], true)) {
            $datosActualizar['fin_evento_at'] = now()->addMinutes($minutosTotales);
            $datosActualizar['pausado_at'] = null;
            do {
                $nuevaSemilla = Str::random(16);
            } while ($nuevaSemilla === $config->semilla_partida);
            $datosActualizar['semilla_partida'] = $nuevaSemilla;
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

        return $this->responderExitoAdmin($request, 'Configuración y tiempos del evento actualizados en vivo.', [
            'estado_juego' => $nuevoEstado,
            'bloqueado'    => $this->esPartidaBloqueada($config->fresh()),
        ]);
    }
    // Modificar cantidad de preguntas, puntos o tiempo de cualquier Etapa/Nivel (Escalabilidad)
    public function actualizarNivel(Request $request, $id)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        if ($this->esPartidaBloqueada(ConfiguracionEvento::first())) {
            return $this->responderBloqueoPartida($request, 'No puedes modificar etapas mientras la partida está en curso, pausada o en desempate.');
        }

        $validated = $request->validate([
            'nombre'             => 'required|string|max:100',
            'cantidad_preguntas' => 'required|integer|between:1,50',
            'puntos_por_acierto' => 'required|integer|between:1,10000',
            'tiempo_limite_seg'  => 'required|integer|between:5,300',
        ]);

        $nivel = Nivel::findOrFail($id);
        $nivel->update($validated);

        return $this->responderExitoAdmin($request, 'Etapa actualizada correctamente.');
    }

    // Agregar cualquier módulo/categoría nuevo
    public function crearModulo(Request $request)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        if ($this->esPartidaBloqueada(ConfiguracionEvento::first())) {
            return $this->responderBloqueoPartida($request, 'No puedes crear módulos mientras la partida está en curso, pausada o en desempate.');
        }

        $request->validate(['nombre' => 'required|string|max:80']);
        Modulo::create([
            'nombre'      => $request->nombre,
            'descripcion' => $request->descripcion,
            'activo'      => true,
        ]);

        return $this->responderExitoAdmin($request, 'Nuevo módulo creado.');
    }

    // Agregar nuevas preguntas a cualquier módulo y etapa
    public function crearPregunta(Request $request)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        if ($this->esPartidaBloqueada(ConfiguracionEvento::first())) {
            return $this->responderBloqueoPartida($request, 'No puedes agregar preguntas mientras la partida está en curso, pausada o en desempate.');
        }

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

        $validated['es_amena'] = $request->boolean('es_amena');

        Pregunta::create($validated);
        return $this->responderExitoAdmin($request, 'Pregunta agregada al banco exitosamente.');
    }

    // Importar lote de preguntas desde archivo JSON o CSV con deduplicación automática
    public function importarPreguntas(Request $request)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        if ($this->esPartidaBloqueada(ConfiguracionEvento::first())) {
            return $this->responderBloqueoPartida($request, 'No puedes importar preguntas mientras la partida está en curso, pausada o en desempate.');
        }

        $request->validate([
            'archivo' => 'required|file|max:5120',
        ]);

        $archivo = $request->file('archivo');
        $ext = strtolower((string) $archivo->getClientOriginalExtension());
        $contenido = (string) file_get_contents($archivo->getRealPath());
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido); // Quitar BOM UTF-8 si existe

        $items = [];
        $trimContenido = ltrim($contenido);

        if ($ext === 'json' || str_starts_with($trimContenido, '[') || str_starts_with($trimContenido, '{')) {
            $decoded = json_decode($contenido, true);
            if (!is_array($decoded)) {
                return back()->withErrors(['archivo' => 'El archivo JSON no tiene un formato válido.']);
            }
            $items = (isset($decoded['preguntas']) && is_array($decoded['preguntas'])) ? $decoded['preguntas'] : $decoded;
        } else {
            $lineas = preg_split('/\r\n|\r|\n/', trim($contenido));
            if (!$lineas || count($lineas) < 2) {
                return back()->withErrors(['archivo' => 'El archivo CSV está vacío o no incluye filas de datos.']);
            }
            $delimitador = (substr_count($lineas[0], ';') > substr_count($lineas[0], ',')) ? ';' : ',';
            $cabeceras = array_map(fn ($h) => strtolower(trim((string) $h)), str_getcsv(array_shift($lineas), $delimitador));

            foreach ($lineas as $linea) {
                if (trim($linea) === '') {
                    continue;
                }
                $valores = str_getcsv($linea, $delimitador);
                $fila = [];
                foreach ($cabeceras as $idx => $col) {
                    if ($col !== '') {
                        $fila[$col] = $valores[$idx] ?? '';
                    }
                }
                $items[] = $fila;
            }
        }

        $modulos = Modulo::all();
        $modulosPorId = $modulos->keyBy('id');
        $modulosPorNombre = $modulos->mapWithKeys(fn ($m) => [mb_strtolower(trim($m->nombre), 'UTF-8') => $m->id]);

        $niveles = Nivel::all();
        $nivelesPorId = $niveles->keyBy('id');
        $nivelesPorOrden = $niveles->mapWithKeys(fn ($n) => [(int) $n->orden_dificultad => $n->id]);
        $nivelesPorNombre = $niveles->mapWithKeys(fn ($n) => [mb_strtolower(trim($n->nombre), 'UTF-8') => $n->id]);

        $existentes = Pregunta::pluck('enunciado')
            ->mapWithKeys(fn ($e) => [mb_strtolower(trim((string) $e), 'UTF-8') => true])
            ->all();

        $nuevas = [];
        $importadas = 0;
        $omitidas = 0;
        $now = now();

        foreach ($items as $item) {
            if (!is_array($item)) {
                $omitidas++;
                continue;
            }

            // 1. Resolver modulo_id
            $moduloId = null;
            if (!empty($item['modulo_id']) && isset($modulosPorId[(int) $item['modulo_id']])) {
                $moduloId = (int) $item['modulo_id'];
            } elseif (!empty($item['modulo'])) {
                $claveMod = mb_strtolower(trim((string) $item['modulo']), 'UTF-8');
                $moduloId = $modulosPorNombre[$claveMod] ?? null;
            }

            // 2. Resolver nivel_id
            $nivelId = null;
            if (!empty($item['nivel_id']) && isset($nivelesPorId[(int) $item['nivel_id']])) {
                $nivelId = (int) $item['nivel_id'];
            } else {
                $refNivel = $item['orden_dificultad'] ?? $item['etapa'] ?? $item['nivel'] ?? null;
                if ($refNivel !== null && $refNivel !== '') {
                    if (is_numeric($refNivel) && isset($nivelesPorOrden[(int) $refNivel])) {
                        $nivelId = (int) $nivelesPorOrden[(int) $refNivel];
                    } else {
                        $claveNiv = mb_strtolower(trim((string) $refNivel), 'UTF-8');
                        $nivelId = $nivelesPorNombre[$claveNiv] ?? null;
                    }
                }
            }

            $enunciado = trim((string) ($item['enunciado'] ?? ''));
            $opA = trim((string) ($item['opcion_a'] ?? ''));
            $opB = trim((string) ($item['opcion_b'] ?? ''));
            $opC = trim((string) ($item['opcion_c'] ?? ''));
            $opD = trim((string) ($item['opcion_d'] ?? ''));
            $correcta = strtolower(trim((string) ($item['respuesta_correcta'] ?? '')));

            if (!$moduloId || !$nivelId || $enunciado === '' || $opA === '' || $opB === '' || $opC === '' || $opD === '' || !in_array($correcta, ['a', 'b', 'c', 'd'], true)) {
                $omitidas++;
                continue;
            }

            $claveEnunciado = mb_strtolower($enunciado, 'UTF-8');
            if (isset($existentes[$claveEnunciado])) {
                $omitidas++;
                continue;
            }

            $existentes[$claveEnunciado] = true;
            $esAmena = filter_var($item['es_amena'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $nuevas[] = [
                'modulo_id'          => $moduloId,
                'nivel_id'           => $nivelId,
                'enunciado'          => $enunciado,
                'opcion_a'           => $opA,
                'opcion_b'           => $opB,
                'opcion_c'           => $opC,
                'opcion_d'           => $opD,
                'respuesta_correcta' => $correcta,
                'activa'             => true,
                'es_amena'           => $esAmena,
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
            $importadas++;
        }

        foreach (array_chunk($nuevas, 50) as $chunk) {
            Pregunta::insert($chunk);
        }

        return $this->responderExitoAdmin($request, "Importación completada: {$importadas} preguntas nuevas añadidas y {$omitidas} omitidas.");
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

        $borrarEquipos = $request->boolean('borrar_equipos');

        DB::transaction(function () use ($borrarEquipos) {
            // Orden estricto de locks: primero ConfiguracionEvento
            $config = ConfiguracionEvento::lockForUpdate()->first();

            do {
                $nuevaSemilla = Str::random(16);
            } while ($config && $nuevaSemilla === $config->semilla_partida);

            if ($config) {
                $config->update([
                    'estado_juego'    => 'registro',
                    'fin_evento_at'   => null,
                    'pausado_at'      => null,
                    'semilla_partida' => $nuevaSemilla,
                ]);
            }

            // Borrado DML transaccional (sin TRUNCATE para evitar commit implícito en MySQL)
            RespuestaEquipo::query()->delete();
            Entrega::query()->delete();

            if ($borrarEquipos) {
                Integrante::query()->delete();
                Equipo::query()->delete();
            } else {
                Equipo::query()->update([
                    'fase_actual'           => 1,
                    'preguntas_contestadas' => 0,
                    'aciertos_totales'      => 0,
                    'racha_actual'          => 0,
                    'racha_maxima'          => 0,
                    'puntaje_total'         => 0,
                    'tiempo_acumulado_ms'   => 0,
                    'salidas_pantalla'      => 0,
                    'en_desempate'          => false,
                    'finalizo_carrera'      => false,
                ]);
            }
        });

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
            do {
                $nuevaSemilla = Str::random(16);
            } while ($nuevaSemilla === $config->semilla_partida);

            $config->update([
                'estado_juego'    => 'en_curso',
                'fin_evento_at'   => now()->addMinutes($config->tiempo_total_minutos ?? 15),
                'pausado_at'      => null,
                'semilla_partida' => $nuevaSemilla,
            ]);
        } elseif (empty($config->semilla_partida) || $config->semilla_partida === 'seventech_init') {
            $config->update(['semilla_partida' => Str::random(16)]);
        }

        $semilla = (string) $config->semilla_partida;
        $niveles = Nivel::where('es_desempate', false)->orderBy('orden_dificultad', 'asc')->get();
        $equipos = Equipo::all();
        $algunoAvanzo = false;

        foreach ($equipos as $equipo) {
            $respondioTurno = false;
            foreach ($niveles as $nivel) {
                $estadisticasNivel = RespuestaEquipo::where('respuestas_equipos.equipo_id', $equipo->id)
                    ->where('respuestas_equipos.nivel_id', $nivel->id)
                    ->join('preguntas', 'respuestas_equipos.pregunta_id', '=', 'preguntas.id')
                    ->selectRaw('COUNT(*) as total, SUM(CASE WHEN preguntas.es_amena = 1 THEN 1 ELSE 0 END) as amenas')
                    ->first();

                $contestadas = (int) ($estadisticasNivel->total ?? 0);
                $amenasContestadas = (int) ($estadisticasNivel->amenas ?? 0);

                if ($contestadas < $nivel->cantidad_preguntas) {
                    $prefiereAmena = 0;
                    if (!$nivel->es_desempate && $nivel->cantidad_preguntas >= 2) {
                        $turnoAmena = hexdec(substr(hash('sha256', "amena:{$nivel->id}:{$semilla}"), 0, 8)) % $nivel->cantidad_preguntas;
                        if ($amenasContestadas === 0 && ($contestadas === $turnoAmena || $contestadas >= $nivel->cantidad_preguntas - 1)) {
                            $prefiereAmena = 1;
                        }
                    }

                    $respondidasIds = RespuestaEquipo::where('equipo_id', $equipo->id)->pluck('pregunta_id');
                    $queryPregunta = Pregunta::where('nivel_id', $nivel->id)
                        ->where('activa', true)
                        ->whereNotIn('id', $respondidasIds);

                    if ($config->modulo_activo_id) {
                        $queryPregunta->where('modulo_id', $config->modulo_activo_id);
                    }

                    $pregunta = $queryPregunta
                        ->orderByRaw('CASE WHEN preguntas.es_amena = ? THEN 0 ELSE 1 END ASC', [$prefiereAmena])
                        ->orderByRaw('CRC32(SHA2(CONCAT(preguntas.id, "-", ?), 256))', [$semilla])
                        ->orderBy('preguntas.id')
                        ->first();

                    if ($pregunta) {
                        $acierta = (rand(1, 100) <= 72);
                        $tiempoMs = rand(2200, 13500);
                        $puntos = $acierta ? $nivel->puntos_por_acierto : 0;

                        RespuestaEquipo::create([
                            'equipo_id'           => $equipo->id,
                            'pregunta_id'         => $pregunta->id,
                            'nivel_id'            => $nivel->id,
                            'opcion_elegida'      => $acierta ? $pregunta->getRawOriginal('respuesta_correcta') : 'a',
                            'es_correcta'         => $acierta,
                            'puntos_ganados'      => $puntos,
                            'tiempo_respuesta_ms' => $tiempoMs,
                        ]);

                        $equipo->increment('preguntas_contestadas');
                        $equipo->increment('tiempo_acumulado_ms', $tiempoMs);

                        if ($acierta) {
                            $nuevaRacha = (int) $equipo->racha_actual + 1;
                            $nuevaRachaMax = max((int) $equipo->racha_maxima, $nuevaRacha);
                            $equipo->increment('aciertos_totales');
                            $equipo->increment('puntaje_total', $puntos);
                            $equipo->update([
                                'fase_actual'  => $nivel->orden_dificultad,
                                'racha_actual' => $nuevaRacha,
                                'racha_maxima' => $nuevaRachaMax,
                            ]);
                        } else {
                            $equipo->update([
                                'fase_actual'  => $nivel->orden_dificultad,
                                'racha_actual' => 0,
                            ]);
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

        if ($this->esPartidaBloqueada(ConfiguracionEvento::first())) {
            return $this->responderBloqueoPartida($request, 'No puedes editar preguntas mientras la partida está en curso, pausada o en desempate.');
        }

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

        $validated['es_amena'] = $request->boolean('es_amena');

        $pregunta = Pregunta::findOrFail($id);
        $pregunta->update($validated);

        return $this->responderExitoAdmin($request, 'Pregunta actualizada correctamente en la base de datos.');
    }

    // Eliminar una pregunta del banco
    public function eliminarPregunta(Request $request, $id)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        if ($this->esPartidaBloqueada(ConfiguracionEvento::first())) {
            return $this->responderBloqueoPartida($request, 'No puedes eliminar preguntas mientras la partida está en curso, pausada o en desempate.');
        }

        Pregunta::findOrFail($id)->delete();
        return $this->responderExitoAdmin($request, 'Pregunta eliminada del banco.');
    }

    // Eliminar un módulo completo
    public function eliminarModulo(Request $request, $id)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        if ($this->esPartidaBloqueada(ConfiguracionEvento::first())) {
            return $this->responderBloqueoPartida($request, 'No puedes eliminar módulos mientras la partida está en curso, pausada o en desempate.');
        }

        if (Modulo::count() <= 1) {
            return $this->responderExitoAdmin($request, 'Debe existir al menos 1 módulo en el sistema.');
        }

        Modulo::findOrFail($id)->delete();
        return $this->responderExitoAdmin($request, 'Módulo eliminado.');
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