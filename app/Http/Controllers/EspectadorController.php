<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionEvento;
use App\Models\Equipo;
use App\Models\Nivel;
use Illuminate\Http\Request;

class EspectadorController extends Controller
{
    public function index(Request $request)
    {
        $host = strtolower((string) $request->getHost());
        $esHostLocal = in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true);
        $mostrarAvisoLocalhost = false;

        if ($esHostLocal) {
            $ipLan = $this->resolverIpLanLocal();
            $puerto = (int) $request->getPort();
            $esquema = $request->getScheme();

            if ($ipLan !== null) {
                $sufijoPuerto = (($esquema === 'http' && $puerto === 80) || ($esquema === 'https' && $puerto === 443) || $puerto <= 0)
                    ? ''
                    : ':' . $puerto;
                $baseOrigen = "{$esquema}://{$ipLan}{$sufijoPuerto}";
            } else {
                $appUrl = rtrim((string) config('app.url'), '/');
                $appHost = strtolower((string) parse_url($appUrl, PHP_URL_HOST));
                if ($appUrl !== '' && !in_array($appHost, ['', 'localhost', '127.0.0.1', '0.0.0.0', '::1'], true)) {
                    $baseOrigen = $appUrl;
                } else {
                    $baseOrigen = rtrim($request->getSchemeAndHttpHost(), '/');
                    $mostrarAvisoLocalhost = true;
                }
            }
        } else {
            $baseOrigen = rtrim($request->getSchemeAndHttpHost(), '/');
        }

        $urlRegistroQr = $baseOrigen . '/jugar';

        return view('espectador.index', compact('urlRegistroQr', 'mostrarAvisoLocalhost'));
    }

    /**
     * Detecta la IPv4 LAN física del servidor ignorando interfaces virtuales (Docker, KVM, VPN).
     */
    protected function resolverIpLanLocal(): ?string
    {
        $prefijosIgnorados = ['lo', 'docker', 'virbr', 'veth', 'br-', 'tun', 'tap', 'vmnet'];
        $prefijosPrioritarios = ['wl', 'en', 'eth', 'wi-fi', 'ethernet'];

        if (function_exists('net_get_interfaces')) {
            $interfaces = @net_get_interfaces();
            if (is_array($interfaces)) {
                $candidatasPrioritarias = [];
                $candidatasSecundarias = [];

                foreach ($interfaces as $nombre => $info) {
                    if (!is_array($info) || !($info['up'] ?? true)) {
                        continue;
                    }

                    $nombreLower = strtolower((string) $nombre);
                    $esVirtual = false;
                    foreach ($prefijosIgnorados as $prefijo) {
                        if (str_starts_with($nombreLower, $prefijo)) {
                            $esVirtual = true;
                            break;
                        }
                    }
                    if ($esVirtual) {
                        continue;
                    }

                    $esPrioritaria = false;
                    foreach ($prefijosPrioritarios as $prefijo) {
                        if (str_starts_with($nombreLower, $prefijo)) {
                            $esPrioritaria = true;
                            break;
                        }
                    }

                    foreach (($info['unicast'] ?? []) as $unicast) {
                        $ip = $unicast['address'] ?? null;
                        if (!is_string($ip) || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                            continue;
                        }
                        if (str_starts_with($ip, '127.') || str_starts_with($ip, '169.254.')) {
                            continue;
                        }
                        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
                            if ($esPrioritaria) {
                                $candidatasPrioritarias[] = $ip;
                            } else {
                                $candidatasSecundarias[] = $ip;
                            }
                        }
                    }
                }

                if (!empty($candidatasPrioritarias)) {
                    return $candidatasPrioritarias[0];
                }
                if (!empty($candidatasSecundarias)) {
                    return $candidatasSecundarias[0];
                }
            }
        }

        $hostname = @gethostname();
        if (is_string($hostname) && $hostname !== '') {
            $hostIp = @gethostbyname($hostname);
            if (
                filter_var($hostIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                && !str_starts_with($hostIp, '127.')
                && !str_starts_with($hostIp, '169.254.')
                && !filter_var($hostIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)
            ) {
                return $hostIp;
            }
        }

        return null;
    }

    // Endpoint en tiempo real para mover los robots en la pista 2D
    public function datosCarreraEnVivo()
    {
        $config = ConfiguracionEvento::with('moduloActivo')->first();
        // La semilla permitiría calcular el orden de preguntas: nunca se publica
        $config?->makeHidden('semilla_partida');
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
                    'racha_actual'          => (int) $equipo->racha_actual,
                    'racha_maxima'          => (int) $equipo->racha_maxima,
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