<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConfiguracionEvento;
use App\Models\Modulo;
use App\Models\Nivel;
use App\Models\Pregunta;
use App\Models\Equipo;
use App\Models\RespuestaEquipo;

class AdminController extends Controller
{
    // Clave maestra de acceso rápido para el panel Admin (además de sesión)
    private string $claveAdmin = 'seventech2026';

    public function index(Request $request)
    {
        $autorizado = $request->session()->get('es_admin_seventech', false);
        $config = ConfiguracionEvento::first();
        $modulos = Modulo::withCount('preguntas')->get();
        $niveles = Nivel::orderBy('orden_dificultad', 'asc')->get();
        $preguntas = Pregunta::with(['modulo', 'nivel'])->latest()->get();
        $equipos = Equipo::with('integrantes')->orderBy('puntaje_total', 'desc')->orderBy('tiempo_acumulado_ms', 'asc')->get();

        return view('admin.index', compact('autorizado', 'config', 'modulos', 'niveles', 'preguntas', 'equipos'));
    }

    public function login(Request $request)
    {
        if ($request->password === $this->claveAdmin) {
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

    // Cambiar estado del juego (registro, en_curso, pausado, finalizado) o módulo activo
    public function actualizarConfiguracion(Request $request)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        $config = ConfiguracionEvento::first();
        $config->update($request->only([
            'estado_juego',
            'modulo_activo_id',
            'max_integrantes_equipo',
            'max_equipos_permitidos'
        ]));

        return back()->with('ok', 'Configuración del evento actualizada en vivo.');
    }

    // Modificar cantidad de preguntas, puntos o tiempo de cualquier Etapa/Nivel (Escalabilidad)
    public function actualizarNivel(Request $request, $id)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        $nivel = Nivel::findOrFail($id);
        $nivel->update($request->only(['nombre', 'cantidad_preguntas', 'puntos_por_acierto', 'tiempo_limite_seg']));

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

        $request->validate([
            'modulo_id'          => 'required|exists:modulos,id',
            'nivel_id'           => 'required|exists:niveles,id',
            'enunciado'          => 'required|string',
            'opcion_a'           => 'required|string',
            'opcion_b'           => 'required|string',
            'opcion_c'           => 'required|string',
            'opcion_d'           => 'required|string',
            'respuesta_correcta' => 'required|in:a,b,c,d',
        ]);

        Pregunta::create($request->all());
        return back()->with('ok', 'Pregunta agregada al banco exitosamente.');
    }

    // Activar Ronda de Desempate (Muerte Súbita) entre los equipos punteros
    public function activarDesempate(Request $request)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

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

    // Reiniciar puntajes o limpiar equipos para una nueva partida
    public function reiniciarJuego(Request $request)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        RespuestaEquipo::truncate();
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

        ConfiguracionEvento::first()->update(['estado_juego' => 'registro']);
        return back()->with('ok', 'Sistema reiniciado y listo para empezar.');
    }
    // Generar equipos de prueba para ver la carrera llena
    public function generarEquiposPrueba(Request $request)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        $nombresBots = ['CyberTitans UEB', 'Linuxeros Guaranda', 'NullPointers', 'Quantum Coders', 'RedHat Squad'];
        $colores = ['#10B981', '#F59E0B', '#A855F7', '#EC4899', '#38BDF8'];

        foreach ($nombresBots as $i => $nombre) {
            if (!Equipo::where('nombre_equipo', $nombre)->exists()) {
                $eq = Equipo::create([
                    'nombre_equipo' => $nombre,
                    'token_sesion'  => \Illuminate\Support\Str::random(60),
                    'color_carril'  => $colores[$i % count($colores)],
                    'fase_actual'   => 1,
                ]);
                \App\Models\Integrante::create([
                    'equipo_id'       => $eq->id,
                    'nombre_completo' => 'Bot Capitán ' . ($i + 1),
                ]);
            }
        }

        return back()->with('ok', '¡Equipos de prueba agregados a la pista! Ábrelos en /espectador.');
    }

    // Simular que todos los equipos avanzan 1 pregunta (para probar la carrera en vivo)
    public function simularAvanceCarrera(Request $request)
    {
        if (!$request->session()->get('es_admin_seventech')) abort(403);

        $niveles = Nivel::where('es_desempate', false)->orderBy('orden_dificultad', 'asc')->get();
        $equipos = Equipo::all();

        foreach ($equipos as $equipo) {
            foreach ($niveles as $nivel) {
                $contestadas = RespuestaEquipo::where('equipo_id', $equipo->id)->where('nivel_id', $nivel->id)->count();
                if ($contestadas < $nivel->cantidad_preguntas) {
                    $respondidasIds = RespuestaEquipo::where('equipo_id', $equipo->id)->pluck('pregunta_id');
                    $pregunta = Pregunta::where('nivel_id', $nivel->id)->whereNotIn('id', $respondidasIds)->first();

                    if ($pregunta) {
                        $acierta = (rand(1, 100) <= 70); // 70% probabilidad de acertar
                        $tiempoMs = rand(2500, 14000);
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
                    }
                    break;
                }
            }
        }

        return back()->with('ok', '¡Avance simulado! Mira cómo se movieron los robots en /espectador.');
    }
}