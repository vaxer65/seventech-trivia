<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionEvento;
use App\Models\Equipo;
use App\Models\Nivel;

class EspectadorController extends Controller
{
    public function index()
    {
        return view('espectador.index');
    }

    // Endpoint en tiempo real para mover los robots en la pista 2D
    public function datosCarreraEnVivo()
    {
        $config = ConfiguracionEvento::with('moduloActivo')->first();
        $niveles = Nivel::where('es_desempate', false)->orderBy('orden_dificultad', 'asc')->get();

        // Calculamos el puntaje máximo posible de las etapas normales para medir el % de la pista
        $puntajeMaximoPista = 0;
        $totalPreguntasCarrera = 0;
        foreach ($niveles as $n) {
            $puntajeMaximoPista += ($n->cantidad_preguntas * $n->puntos_por_acierto);
            $totalPreguntasCarrera += $n->cantidad_preguntas;
        }
        $puntajeMaximoPista = max($puntajeMaximoPista, 100);

        // Ordenamos los equipos: 1° Mayor Puntaje, 2° Mayor Aciertos, 3° Menor Tiempo (Desempate por velocidad)
        $equipos = Equipo::withCount('integrantes')
            ->orderBy('puntaje_total', 'desc')
            ->orderBy('aciertos_totales', 'desc')
            ->orderBy('tiempo_acumulado_ms', 'asc')
            ->get()
            ->map(function ($equipo, $index) use ($puntajeMaximoPista, $totalPreguntasCarrera) {
                // El avance del robot en la pista 2D combina el puntaje real (85%) y el avance de etapas (15%)
                // Así quien tiene más puntos SIEMPRE va adelante en la carrera visual
                $ratioPuntos = min(1, $equipo->puntaje_total / $puntajeMaximoPista);
                $ratioAvance = $totalPreguntasCarrera > 0 ? min(1, $equipo->preguntas_contestadas / $totalPreguntasCarrera) : 0;

                $porcentajePista = round(($ratioPuntos * 85) + ($ratioAvance * 15), 1);

                return [
                    'posicion'              => $index + 1,
                    'id'                    => $equipo->id,
                    'nombre_equipo'         => $equipo->nombre_equipo,
                    'color_carril'          => $equipo->color_carril,
                    'integrantes_count'     => $equipo->integrantes_count,
                    'fase_actual'           => $equipo->fase_actual,
                    'preguntas_contestadas' => $equipo->preguntas_contestadas,
                    'total_preguntas'       => $totalPreguntasCarrera,
                    'aciertos_totales'      => $equipo->aciertos_totales,
                    'puntaje_total'         => $equipo->puntaje_total,
                    'tiempo_segundos'       => round($equipo->tiempo_acumulado_ms / 1000, 2),
                    'porcentaje_pista'      => max(3, min(100, $porcentajePista)), // Mínimo 3% para verse en la salida
                    'en_desempate'          => $equipo->en_desempate,
                    'finalizo_carrera'      => $equipo->finalizo_carrera,
                ];
            });

        return response()->json([
            'evento'  => $config,
            'niveles' => $niveles,
            'equipos' => $equipos,
        ]);
    }
}