<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Seven Tech - Panel de Administración</title>
    @include('partials.cyberpunk-head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
    <style>
        .notyf__toast {
            border-radius: 12px !important;
            border: 1px solid rgba(0, 229, 255, 0.45);
            box-shadow: 0 0 20px rgba(0, 229, 255, 0.2) !important;
            font-family: 'Rajdhani', sans-serif !important;
            font-weight: 700 !important;
        }
        [data-lockable="true"]:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="circuit-bg min-h-screen p-4 md:p-6">

    @if(!$autorizado)
        <!-- PANTALLA DE LOGIN ADMIN -->
        <div class="max-w-md mx-auto mt-20 card rounded-2xl p-6 shadow-2xl">
            <h1 class="text-2xl font-black text-[#00E5FF] mb-1">🔐 Admin Seven Tech</h1>
            <p class="text-xs text-slate-300 mb-5">Ingresa la clave maestra para controlar la trivia en vivo.</p>

            <form method="POST" action="/admin/login" class="space-y-4">
                @csrf
                <div>
                    <label class="text-xs font-bold text-slate-300">Contraseña Maestra</label>
                    <input type="password" name="password" required placeholder="Contraseña de administrador"
                           class="w-full mt-1 bg-[#07101E] border {{ $errors->has('password') ? 'border-rose-500 focus:border-rose-500' : 'border-slate-700 focus:border-[#00E5FF]' }} rounded-xl px-4 py-3 text-sm text-white outline-none">
                    @error('password')
                        <p class="mt-1.5 text-xs font-bold text-rose-400">⚠️ {{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="w-full py-3 rounded-xl bg-[#00E5FF] text-[#07101E] font-black text-sm uppercase">
                    Entrar al Panel de Control
                </button>
            </form>
        </div>
    @else
        @php
            $partidaBloqueada = in_array($config->estado_juego, ['en_curso', 'pausado', 'desempate'], true);
        @endphp
        <!-- PANEL DE CONTROL COMPLETO -->
        <header class="max-w-7xl mx-auto flex flex-wrap justify-between items-center card rounded-2xl p-4 mb-6">
            <div class="flex items-center gap-3">
                <a href="/" title="Ir al Portal Principal">
                    <img src="/favicon.svg" alt="Seven Tech" class="w-11 h-11 rounded-xl">
                </a>
                <div>
                    <h1 class="text-xl font-black text-[#00E5FF]">CENTRO DE MANDO SEVEN TECH</h1>
                    <p class="text-xs text-slate-300">Estado actual: <b id="badge-estado-admin" class="uppercase text-amber-400">{{ $config->estado_juego }}</b></p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3 mt-2 sm:mt-0">
                <a href="/espectador" target="_blank" class="px-3.5 py-2.5 rounded-xl bg-cyan-500/20 border border-[#00E5FF] text-[#00E5FF] text-xs font-bold">
                    🖥️ Abrir Pantalla Espectador
                </a>
                <a href="/jugar" target="_blank" class="px-3.5 py-2.5 rounded-xl bg-emerald-500/20 border border-emerald-400 text-emerald-300 text-xs font-bold">
                    🎮 Abrir Vista Concursante
                </a>
                <form method="POST" action="/admin/logout">
                    @csrf
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 border border-rose-500/60 text-rose-200 text-xs font-bold flex items-center gap-1.5 transition">
                        <span>🚪</span>
                        <span>Cerrar Sesión</span>
                    </button>
                </form>
            </div>
        </header>

        @if(session('ok'))
            <div class="max-w-7xl mx-auto mb-6 p-3.5 rounded-xl bg-emerald-500/20 border border-emerald-400 text-emerald-200 text-sm font-bold">
                ✅ {{ session('ok') }}
            </div>
        @endif
        @if($errors->any())
            <div class="max-w-7xl mx-auto mb-6 p-3.5 rounded-xl bg-rose-500/20 border border-rose-500 text-rose-200 text-sm font-bold">
                ⚠️ {{ $errors->first() }}
            </div>
        @endif

        <main class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- 1. CONTROL DE ESTADO DEL EVENTO Y DESEMPATE -->
            <section class="card rounded-2xl p-5 space-y-5">
                <h2 class="text-base font-extrabold text-[#00E5FF] border-b border-slate-800 pb-2">1. Control de Carrera en Vivo</h2>

                <div id="aviso-bloqueo-config" class="{{ $partidaBloqueada ? '' : 'hidden' }} p-2.5 rounded-xl bg-amber-500/15 border border-amber-400/50 text-amber-200 text-[11px] font-bold">
                    🔒 Bloqueado mientras hay una partida en curso/pausa (solo editable en Registro o Finalizado).
                </div>

                <form id="form-configuracion" method="POST" action="/admin/configuracion" class="space-y-3">
                    @csrf
                    <div>
                        <label class="text-xs font-bold text-slate-300">Estado de la Trivia</label>
                        <select name="estado_juego" class="w-full mt-1 bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white">
                            <option value="registro" {{ $config->estado_juego == 'registro' ? 'selected' : '' }}>🟢 Registro Abierto (En espera)</option>
                            <option value="en_curso" {{ $config->estado_juego == 'en_curso' ? 'selected' : '' }}>🚀 EN CURSO (¡Corriendo Carrera!)</option>
                            <option value="pausado" {{ $config->estado_juego == 'pausado' ? 'selected' : '' }}>⏸️ Pausado</option>
                            <option value="finalizado" {{ $config->estado_juego == 'finalizado' ? 'selected' : '' }}>🏁 Finalizado</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-300">Módulo / Categoría Activa</label>
                        <select name="modulo_activo_id" data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} class="w-full mt-1 bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white">
                            <option value="" {{ is_null($config->modulo_activo_id) ? 'selected' : '' }}>
                                🌐 Todos los Módulos (Sorteo Global - {{ $preguntas->count() }} pregs)
                            </option>
                            @foreach($modulos as $mod)
                                <option value="{{ $mod->id }}" {{ $config->modulo_activo_id == $mod->id ? 'selected' : '' }}>
                                    {{ $mod->nombre }} ({{ $mod->preguntas_count }} pregs)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="text-[11px] text-slate-400">Máx. Integrantes</label>
                            <input type="number" name="max_integrantes_equipo" value="{{ $config->max_integrantes_equipo }}" class="w-full bg-[#07101E] border border-slate-700 rounded-xl px-2.5 py-2 text-sm text-white">
                        </div>
                        <div>
                            <label class="text-[11px] text-slate-400">Máx. Equipos</label>
                            <input type="number" name="max_equipos_permitidos" value="{{ $config->max_equipos_permitidos }}" class="w-full bg-[#07101E] border border-slate-700 rounded-xl px-2.5 py-2 text-sm text-white">
                        </div>
                        <div>
                            <label class="text-[11px] text-[#00E5FF] font-bold">Trivia (Minutos)</label>
                            <input type="number" name="tiempo_total_minutos" data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} min="1" max="600" value="{{ $config->tiempo_total_minutos ?? 15 }}" class="w-full bg-[#07101E] border border-[#00E5FF] rounded-xl px-2.5 py-2 text-sm text-[#00E5FF] font-bold">
                        </div>
                    </div>

                    @php
                        $semaforoClases = match($estimacionDuracion['semaforo'] ?? 'verde') {
                            'rojo'  => 'bg-rose-500/20 text-rose-300 border-rose-500/60',
                            'ambar' => 'bg-amber-500/20 text-amber-300 border-amber-400/60',
                            default => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/60',
                        };
                        $fmtSeg = static function (int $seg): string {
                            $m = intdiv($seg, 60);
                            $s = $seg % 60;
                            return "{$m}m " . str_pad((string) $s, 2, '0', STR_PAD_LEFT) . 's';
                        };
                    @endphp

                    <!-- Calculadora Inteligente de Duración del Torneo -->
                    <div id="calculadora-duracion-torneo" class="p-3 rounded-xl bg-[#07101E]/90 border border-slate-700/80 space-y-2.5">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <span class="text-[11px] font-bold uppercase tracking-wide text-slate-300">⏱️ Estimador del Torneo</span>
                            <span id="semaforo-duracion" class="px-2.5 py-0.5 rounded-full border text-[11px] font-bold {{ $semaforoClases }}">
                                {{ $estimacionDuracion['semaforo_texto'] }}
                            </span>
                        </div>
                        <p id="semaforo-duracion-detalle" class="text-[11px] text-slate-400 leading-snug">
                            {{ $estimacionDuracion['semaforo_detalle'] }}
                        </p>

                        <div class="grid grid-cols-3 gap-1.5 text-center">
                            <div class="bg-[#0F2342]/80 rounded-lg p-1.5 border border-slate-800">
                                <span class="block text-[10px] text-slate-400">Ritmo Prom.</span>
                                <strong id="calc-ritmo-promedio" class="text-xs text-slate-100">{{ $fmtSeg($estimacionDuracion['seg_promedio']) }}</strong>
                            </div>
                            <div class="bg-[#0F2342]/80 rounded-lg p-1.5 border border-slate-800">
                                <span class="block text-[10px] text-slate-400">Máx. Teórico</span>
                                <strong id="calc-tiempo-maximo" class="text-xs text-amber-300">{{ $fmtSeg($estimacionDuracion['seg_maximo']) }}</strong>
                            </div>
                            <div class="bg-[#0F2342]/80 rounded-lg p-1.5 border border-[#00E5FF]/40">
                                <span class="block text-[10px] text-[#00E5FF]">Recomendado</span>
                                <strong id="calc-tiempo-recomendado" class="text-xs text-[#00E5FF]">{{ $estimacionDuracion['recomendado_min'] }} min</strong>
                            </div>
                        </div>

                        <div id="calc-desglose-etapas" class="space-y-1 text-[11px] text-slate-300 border-t border-slate-800/80 pt-2">
                            @foreach($estimacionDuracion['desglose_etapas'] as $etapa)
                                <div class="flex items-center justify-between gap-2">
                                    <span class="truncate text-slate-400">{{ $etapa['nombre'] }} ({{ $etapa['preguntas'] }} × {{ $etapa['tiempo_limite'] }}s)</span>
                                    <span class="font-mono text-slate-200 shrink-0">{{ $fmtSeg($etapa['seg_puros']) }}</span>
                                </div>
                            @endforeach
                        </div>

                        <button type="button"
                                id="btn-aplicar-tiempo-recomendado"
                                data-lockable="true"
                                data-recomendado-min="{{ $estimacionDuracion['recomendado_min'] }}"
                                {{ $partidaBloqueada ? 'disabled' : '' }}
                                class="w-full py-2 px-3 rounded-lg bg-[#00E5FF]/15 hover:bg-[#00E5FF]/25 disabled:opacity-45 disabled:cursor-not-allowed border border-[#00E5FF]/60 text-[#00E5FF] font-bold text-[11px] transition">
                            ✨ Aplicar tiempo recomendado ({{ $estimacionDuracion['recomendado_min'] }} min)
                        </button>
                    </div>

                    <button type="submit" class="w-full py-2.5 rounded-xl bg-[#00E5FF] text-[#07101E] font-black text-xs uppercase">
                        Guardar y Aplicar en Vivo
                    </button>
                </form>

                <!-- Botón de Muerte Súbita (Desempate) -->
                <div class="pt-3 border-t border-slate-800">
                    <h3 class="text-xs font-bold uppercase text-rose-400 mb-2">🔥 Sistema de Desempate</h3>
                    <form method="POST" action="/admin/desempate">
                        @csrf
                        <p class="text-xs text-slate-400 mb-2">Selecciona equipos específicos abajo o actívalo directo para que compitan los mejores empatados:</p>
                        <div class="max-h-28 overflow-y-auto space-y-1 mb-3 bg-[#07101E] p-2 rounded-xl border border-slate-800">
                            @forelse($equipos as $eq)
                                <label class="flex items-center gap-2 text-xs">
                                    <input type="checkbox" name="equipos_desempate[]" value="{{ $eq->id }}" {{ $eq->en_desempate ? 'checked' : '' }}>
                                    <span>{{ $eq->nombre_equipo }} ({{ $eq->puntaje_total }} pts - {{ round($eq->tiempo_acumulado_ms/1000, 1) }}s)</span>
                                </label>
                            @empty
                                <span class="text-xs text-slate-500">Sin equipos registrados aún.</span>
                            @endforelse
                        </div>
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-black text-xs uppercase">
                            ⚡ Activar Muerte Súbita (Desempate)
                        </button>
                    </form>
                </div>

                <!-- Archivar en Historial de Eventos antes de reiniciar -->
                <div class="pt-3 border-t border-slate-800">
                    <h3 class="text-xs font-bold uppercase text-emerald-400 mb-2">🏛️ Guardar en Historial de Eventos</h3>
                    <form method="POST" action="/admin/historial/guardar" class="space-y-2">
                        @csrf
                        <input type="text" name="nombre_edicion" placeholder="Ej: 1er Torneo Seven Tech - Nov 2026"
                               class="w-full bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                        <button type="submit" class="w-full py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-[#07101E] font-black text-xs uppercase">
                            🏆 Archivar Ganadores y Resultados
                        </button>
                    </form>
                </div>

                <!-- Reiniciar Juego -->
                <div class="pt-3 border-t border-slate-800">
                    <form method="POST" action="/admin/reiniciar" onsubmit="return confirm('¿Seguro que deseas reiniciar los puntajes de la partida?');">
                        @csrf
                        <label class="flex items-center gap-2 text-xs text-slate-300 mb-2">
                            <input type="checkbox" name="borrar_equipos" value="1"> Eliminar también todos los equipos registrados
                        </label>
                        <button type="submit" class="w-full py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs">
                            🔄 Reiniciar Partida
                        </button>
                    </form>
                </div>
            </section>

            <!-- 2. ESCALABILIDAD: EDITAR ETAPAS Y CREAR MÓDULOS -->
            <section class="card rounded-2xl p-5 space-y-5">
                <h2 class="text-base font-extrabold text-[#00E5FF] border-b border-slate-800 pb-2">2. Configurar Etapas de la Carrera</h2>
                <div id="aviso-bloqueo-etapas" class="{{ $partidaBloqueada ? '' : 'hidden' }} p-2.5 rounded-xl bg-amber-500/15 border border-amber-400/50 text-amber-200 text-[11px] font-bold">
                    🔒 Bloqueado mientras hay una partida en curso/pausa
                </div>
                <div class="space-y-3">
                    @foreach($niveles as $niv)
                        <form method="POST" action="/admin/nivel/{{ $niv->id }}"
                              data-es-desempate="{{ $niv->es_desempate ? '1' : '0' }}"
                              data-nivel-nombre="{{ $niv->nombre }}"
                              class="form-nivel-admin bg-[#07101E] p-3 rounded-xl border border-slate-800 space-y-2">
                            @csrf
                            <input type="text" name="nombre" data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} value="{{ $niv->nombre }}" class="w-full bg-transparent font-bold text-xs text-[#00E5FF] border-b border-slate-800 pb-1">
                            <div class="grid grid-cols-3 gap-2 text-xs">
                                <div>
                                    <span class="text-[10px] text-slate-400">Preguntas</span>
                                    <input type="number" name="cantidad_preguntas" data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} value="{{ $niv->cantidad_preguntas }}" class="w-full bg-[#0F2342] rounded px-2 py-1 text-white">
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-400">Puntos c/u</span>
                                    <input type="number" name="puntos_por_acierto" data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} value="{{ $niv->puntos_por_acierto }}" class="w-full bg-[#0F2342] rounded px-2 py-1 text-white">
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-400">Segundos</span>
                                    <input type="number" name="tiempo_limite_seg" data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} value="{{ $niv->tiempo_limite_seg }}" class="w-full bg-[#0F2342] rounded px-2 py-1 text-white">
                                </div>
                            </div>
                            <button type="submit" data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} class="w-full py-1.5 rounded-lg bg-cyan-500/20 text-[#00E5FF] font-bold text-xs">Actualizar Etapa</button>
                        </form>
                    @endforeach
                </div>

                <!-- Crear Nuevo Módulo -->
                <div class="pt-3 border-t border-slate-800">
                    <h3 class="text-xs font-bold uppercase text-[#00E5FF] mb-2">➕ Agregar Nuevo Módulo/Categoría</h3>
                    <form method="POST" action="/admin/modulo" class="space-y-2">
                        @csrf
                        <input type="text" name="nombre" required data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} placeholder="Nombre del módulo (Ej: Robótica e IoT)" class="w-full bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                        <input type="text" name="descripcion" data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} placeholder="Descripción corta" class="w-full bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                        <button type="submit" data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} class="w-full py-2 rounded-xl bg-emerald-500 text-[#07101E] font-black text-xs uppercase">Crear Módulo</button>
                    </form>
                </div>
            </section>

            <!-- 3. AGREGAR NUEVAS PREGUNTAS AL BANCO -->
            <section class="card rounded-2xl p-5 space-y-4">
                <h2 class="text-base font-extrabold text-[#00E5FF] border-b border-slate-800 pb-2">3. Añadir Pregunta al Banco</h2>
                <div id="aviso-bloqueo-banco" class="{{ $partidaBloqueada ? '' : 'hidden' }} p-2.5 rounded-xl bg-amber-500/15 border border-amber-400/50 text-amber-200 text-[11px] font-bold">
                    🔒 Bloqueado mientras hay una partida en curso/pausa
                </div>
                <form method="POST" action="/admin/pregunta" class="space-y-2.5 text-xs">
                    @csrf
                    <div>
                        <label class="text-slate-300 font-bold">Módulo</label>
                        <select name="modulo_id" data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} class="w-full mt-1 bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2 text-white">
                            @foreach($modulos as $m)
                                <option value="{{ $m->id }}" {{ ($config->modulo_activo_id ?? null) == $m->id ? 'selected' : '' }}>{{ $m->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-slate-300 font-bold">Etapa / Nivel de Dificultad</label>
                        <select name="nivel_id" data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} class="w-full mt-1 bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2 text-white">
                            @foreach($niveles as $n)
                                <option value="{{ $n->id }}">{{ $n->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-slate-300 font-bold">Enunciado de la Pregunta</label>
                        <textarea name="enunciado" required rows="2" data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} class="w-full mt-1 bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2 text-white"></textarea>
                    </div>
                    <input type="text" name="opcion_a" required data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} placeholder="Opción A" class="w-full bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2 text-white">
                    <input type="text" name="opcion_b" required data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} placeholder="Opción B" class="w-full bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2 text-white">
                    <input type="text" name="opcion_c" required data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} placeholder="Opción C" class="w-full bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2 text-white">
                    <input type="text" name="opcion_d" required data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} placeholder="Opción D" class="w-full bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2 text-white">
                    <div>
                        <label class="text-emerald-400 font-bold">Respuesta Correcta (Secreta)</label>
                        <select name="respuesta_correcta" data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} class="w-full mt-1 bg-[#07101E] border border-emerald-500 rounded-xl px-3 py-2 font-bold text-emerald-300">
                            <option value="a">Opción A</option>
                            <option value="b">Opción B</option>
                            <option value="c">Opción C</option>
                            <option value="d">Opción D</option>
                        </select>
                    </div>
                    <label class="flex items-center gap-2 text-xs text-amber-300 font-semibold pt-1 cursor-pointer">
                        <input type="checkbox" name="es_amena" value="1" data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} class="rounded border-slate-600">
                        <span>🎮 Pregunta amena / curiosa de cultura geek</span>
                    </label>
                    <button type="submit" data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} class="w-full py-2.5 rounded-xl bg-[#00E5FF] text-[#07101E] font-black uppercase">
                        Guardar Pregunta
                    </button>
                </form>

                <!-- Importador Masivo CSV / JSON -->
                <div class="pt-3 border-t border-slate-800">
                    <h3 class="text-xs font-bold uppercase text-[#00E5FF] mb-1.5">📥 Importar Lote de Preguntas (.JSON / .CSV)</h3>
                    <p class="text-[11px] text-slate-400 mb-2">Sube un archivo JSON o CSV para agregar preguntas sin duplicar las existentes.</p>
                    <form method="POST" action="/admin/preguntas/importar" enctype="multipart/form-data" class="space-y-2 text-xs">
                        @csrf
                        <input type="file" name="archivo" accept=".json,.csv,.txt" required data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }}
                               class="w-full bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2 text-slate-300 text-xs">
                        <button type="submit" data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} class="w-full py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-[#07101E] font-black uppercase">
                            📤 Importar Archivo al Banco
                        </button>
                    </form>
                </div>
            </section>

        </main>

        <!-- 3.5 AUDITORÍA DE EQUIPOS E INTEGRANTES REALES (EXCLUSIVO ADMIN - OCULTO EN ESPECTADOR) -->
        <section class="max-w-7xl mx-auto mt-6 card rounded-2xl p-5">
            <div class="flex flex-wrap justify-between items-center border-b border-slate-800 pb-3 mb-4">
                <div>
                    <h2 class="text-lg font-extrabold text-[#00E5FF]">👥 Equipos Registrados y Nombres Reales ({{ $equipos->count() }})</h2>
                    <p class="text-xs text-slate-400">Esta información es confidencial del jurado/administrador y jamás se muestra en la pantalla de espectadores.</p>
                </div>
            </div>

            @if($equipos->isEmpty())
                <p class="text-xs text-slate-500 py-4 text-center">Todavía no hay equipos registrados.</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-[380px] overflow-y-auto pr-1">
                    @foreach($equipos as $eq)
                        <div class="bg-[#07101E] border border-slate-800 rounded-xl p-3.5 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-2 border-b border-slate-800 pb-2 mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-3 h-3 rounded-full" style="background-color: {{ $eq->color_carril }};"></span>
                                        <h3 class="font-bold text-sm text-white">{{ $eq->nombre_equipo }}</h3>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @if(($eq->salidas_pantalla ?? 0) > 0)
                                            <span class="font-orbitron text-[10px] px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 border border-rose-500/60">
                                                ⚠️ {{ $eq->salidas_pantalla }} {{ $eq->salidas_pantalla === 1 ? 'salida de app' : 'salidas de app' }}
                                            </span>
                                        @endif
                                        <span class="font-orbitron text-[10px] px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-400/40">
                                            🔑 PIN: {{ $eq->pin_recuperacion }}
                                        </span>
                                    </div>
                                </div>

                                <p class="text-[11px] font-bold text-[#00E5FF] uppercase mb-1">
                                    Integrantes ({{ $eq->integrantes->count() }}):
                                </p>
                                <ul class="space-y-1 text-xs text-slate-300 mb-3">
                                    @foreach($eq->integrantes as $idx => $int)
                                        <li class="flex items-center gap-1.5">
                                            <span class="text-[10px] text-slate-500">{{ $idx + 1 }}.</span>
                                            <span>{{ $int->nombre_completo }}</span>
                                            @if($idx === 0)
                                                <span class="text-[9px] px-1.5 py-0.2 rounded bg-cyan-500/20 text-[#00E5FF]">Capitán</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="flex items-center justify-between pt-2 border-t border-slate-800/80 text-[11px]">
                                <span class="font-orbitron text-emerald-400 font-bold">{{ $eq->puntaje_total }} PTS • Etapa {{ $eq->fase_actual }}</span>
                                <form method="POST" action="/admin/equipo/{{ $eq->id }}/eliminar" onsubmit="return confirm('¿Eliminar al equipo {{ $eq->nombre_equipo }}?');">
                                    @csrf
                                    <button type="submit" class="text-rose-400 hover:underline font-bold">🗑️ Dar de baja</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <!-- 4. GESTOR COMPLETO DEL BANCO DE PREGUNTAS -->
        <section class="max-w-7xl mx-auto mt-6 card rounded-2xl p-5">
            <div class="flex flex-wrap justify-between items-center border-b border-slate-800 pb-3 mb-4 gap-3">
                <div>
                    <h2 class="text-lg font-extrabold text-[#00E5FF]">4. Banco de Preguntas Registradas ({{ $preguntas->count() }})</h2>
                    <p class="text-xs text-slate-400">Ordenadas por Módulo y Etapa (Fase 1 → Fase 2 → Fase 3 → Desempate).</p>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <select id="filtro-modulo" onchange="filtrarPreguntasAdmin()" class="bg-[#07101E] border border-slate-700 rounded-xl px-3 py-1.5 text-white">
                        <option value="todos">📁 Todos los Módulos</option>
                        @foreach($modulos as $m)
                            <option value="mod-{{ $m->id }}">{{ $m->nombre }}</option>
                        @endforeach
                    </select>

                    <select id="filtro-nivel" onchange="filtrarPreguntasAdmin()" class="bg-[#07101E] border border-slate-700 rounded-xl px-3 py-1.5 text-[#00E5FF] font-bold">
                        <option value="todos">⚡ Todas las Etapas</option>
                        @foreach($niveles as $n)
                            <option value="niv-{{ $n->id }}">{{ $n->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="space-y-3 max-h-[550px] overflow-y-auto pr-1" id="lista-preguntas-admin">
                @foreach($preguntas as $index => $preg)
                    <details class="item-pregunta-admin bg-[#07101E] border border-slate-800 rounded-xl p-3 group"
                             data-modulo="mod-{{ $preg->modulo_id }}"
                             data-nivel="niv-{{ $preg->nivel_id }}">
                        <summary class="cursor-pointer flex items-center justify-between gap-3 text-sm font-bold text-white list-none">
                            <div class="flex flex-wrap items-center gap-2 flex-1">
                                <span class="font-orbitron text-[10px] px-2 py-0.5 rounded bg-slate-800 text-slate-300">
                                    #{{ $index + 1 }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-orbitron font-bold border"
                                      style="color: {{ $preg->nivel->color_hex ?? '#00E5FF' }}; border-color: {{ $preg->nivel->color_hex ?? '#00E5FF' }}; background-color: rgba(0, 229, 255, 0.08);">
                                    {{ $preg->nivel->nombre ?? 'Nivel' }}
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] bg-[#0F2342] text-slate-300 border border-slate-700">
                                    {{ $preg->modulo->nombre ?? 'Módulo' }}
                                </span>
                                @if($preg->es_amena)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-400/50">
                                        🎮 AMENA
                                    </span>
                                @endif
                                <span class="text-white font-semibold">{{ $preg->enunciado }}</span>
                            </div>

                            <div class="shrink-0 flex items-center gap-1.5 text-xs text-[#00E5FF] bg-[#0F2342] px-2.5 py-1 rounded-lg border border-[#00E5FF]/30">
                                <span>Editar / Borrar</span>
                                <span class="inline-block transition-transform duration-200 group-open:rotate-180">▼</span>
                            </div>
                        </summary>

                        <div class="mt-3 pt-3 border-t border-slate-800">
                            <form method="POST" action="/admin/pregunta/{{ $preg->id }}/editar" class="space-y-2 text-xs">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-slate-400">Módulo</label>
                                        <select name="modulo_id" class="w-full bg-[#0F2342] border border-slate-700 rounded-lg px-2 py-1.5 text-white">
                                            @foreach($modulos as $m)
                                                <option value="{{ $m->id }}" {{ $preg->modulo_id == $m->id ? 'selected' : '' }}>{{ $m->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-slate-400">Etapa</label>
                                        <select name="nivel_id" class="w-full bg-[#0F2342] border border-slate-700 rounded-lg px-2 py-1.5 text-white">
                                            @foreach($niveles as $n)
                                                <option value="{{ $n->id }}" {{ $preg->nivel_id == $n->id ? 'selected' : '' }}>{{ $n->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <textarea name="enunciado" rows="2" class="w-full bg-[#0F2342] border border-slate-700 rounded-lg px-3 py-2 text-white">{{ $preg->enunciado }}</textarea>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <input type="text" name="opcion_a" value="{{ $preg->opcion_a }}" class="bg-[#0F2342] border border-slate-700 rounded-lg px-2.5 py-1.5 text-white">
                                    <input type="text" name="opcion_b" value="{{ $preg->opcion_b }}" class="bg-[#0F2342] border border-slate-700 rounded-lg px-2.5 py-1.5 text-white">
                                    <input type="text" name="opcion_c" value="{{ $preg->opcion_c }}" class="bg-[#0F2342] border border-slate-700 rounded-lg px-2.5 py-1.5 text-white">
                                    <input type="text" name="opcion_d" value="{{ $preg->opcion_d }}" class="bg-[#0F2342] border border-slate-700 rounded-lg px-2.5 py-1.5 text-white">
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-2 pt-2">
                                    <div class="flex flex-wrap items-center gap-4">
                                        <div class="flex items-center gap-2">
                                            <span class="text-emerald-400 font-bold">Correcta:</span>
                                            <select name="respuesta_correcta" class="bg-[#0F2342] border border-emerald-500 rounded-lg px-2.5 py-1 font-bold text-emerald-300">
                                                <option value="a" {{ $preg->getRawOriginal('respuesta_correcta') == 'a' ? 'selected' : '' }}>Opción A</option>
                                                <option value="b" {{ $preg->getRawOriginal('respuesta_correcta') == 'b' ? 'selected' : '' }}>Opción B</option>
                                                <option value="c" {{ $preg->getRawOriginal('respuesta_correcta') == 'c' ? 'selected' : '' }}>Opción C</option>
                                                <option value="d" {{ $preg->getRawOriginal('respuesta_correcta') == 'd' ? 'selected' : '' }}>Opción D</option>
                                            </select>
                                        </div>
                                        <label class="flex items-center gap-1.5 text-amber-300 font-semibold cursor-pointer">
                                            <input type="checkbox" name="es_amena" value="1" {{ $preg->es_amena ? 'checked' : '' }} class="rounded border-slate-600">
                                            <span>🎮 Amena / Curiosa</span>
                                        </label>
                                    </div>

                                    <button type="submit" data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} class="px-4 py-1.5 rounded-lg bg-[#00E5FF] text-[#07101E] font-bold uppercase">
                                        💾 Guardar Cambios
                                    </button>
                                </div>
                            </form>

                            <form method="POST" action="/admin/pregunta/{{ $preg->id }}/eliminar" class="mt-2 text-right" onsubmit="return confirm('¿Eliminar esta pregunta definitivamente?');">
                                @csrf
                                <button type="submit" data-lockable="true" {{ $partidaBloqueada ? 'disabled' : '' }} class="px-3 py-1 rounded-lg bg-rose-500/20 border border-rose-500 text-rose-300 text-xs font-bold hover:bg-rose-500/30">
                                    🗑️ Eliminar Pregunta
                                </button>
                            </form>
                        </div>
                    </details>
                @endforeach
            </div>

            <script>
                function filtrarPreguntasAdmin() {
                    const mod = document.getElementById('filtro-modulo').value;
                    const niv = document.getElementById('filtro-nivel').value;
                    document.querySelectorAll('.item-pregunta-admin').forEach(el => {
                        const coincideMod = (mod === 'todos' || el.dataset.modulo === mod);
                        const coincideNiv = (niv === 'todos' || el.dataset.nivel === niv);
                        el.style.display = (coincideMod && coincideNiv) ? '' : 'none';
                    });
                }
            </script>
        </section>

        <!-- 5. HISTORIAL DE EVENTOS PASADOS (SALÓN DE LA FAMA) -->
        <section class="max-w-7xl mx-auto mt-6 card rounded-2xl p-5">
            <h2 class="text-lg font-extrabold text-[#00E5FF] border-b border-slate-800 pb-2 mb-4">
                🏛️ 5. Historial de Eventos y Ganadores ({{ $historial->count() }})
            </h2>
            @if($historial->isEmpty())
                <p class="text-xs text-slate-400">Aún no se han archivado ediciones en el historial.</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($historial as $h)
                        <div class="bg-[#07101E] border border-slate-800 rounded-xl p-4 flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-start gap-2 mb-2">
                                    <div>
                                        <h3 class="font-bold text-base text-white">{{ $h->nombre_edicion }}</h3>
                                        <p class="font-orbitron text-[10px] text-[#00E5FF]">{{ $h->modulo_jugado }} • {{ $h->total_equipos }} Equipos • {{ $h->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                    <form method="POST" action="/admin/historial/{{ $h->id }}/eliminar" onsubmit="return confirm('¿Borrar este evento del historial?');">
                                        @csrf
                                        <button class="text-xs text-rose-400 hover:underline">🗑️</button>
                                    </form>
                                </div>
                                <div class="space-y-1.5 mt-3">
                                    @foreach($h->podio_top3 ?? [] as $ganador)
                                        <div class="p-2 rounded-lg bg-[#0F2342]/70 border border-slate-800 text-xs flex justify-between items-center">
                                            <div>
                                                <span class="font-orbitron font-bold text-amber-400">#{{ $ganador['posicion'] }} {{ $ganador['nombre_equipo'] }}</span>
                                                <p class="text-[11px] text-slate-300">👥 {{ implode(', ', $ganador['integrantes'] ?? []) }}</p>
                                            </div>
                                            <span class="font-orbitron font-bold text-emerald-400">{{ $ganador['puntaje_total'] }} PTS ({{ $ganador['tiempo_segundos'] }}s)</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <!-- 6. MODO ENSAYO / SIMULADOR AUTOMÁTICO DE FLUJO COMPLETO (solo entorno local) -->
        @if(app()->isLocal())
        <section class="max-w-7xl mx-auto mt-6 mb-10">
            <details class="card rounded-2xl p-4 border border-dashed border-slate-700 group">
                <summary class="cursor-pointer flex flex-wrap items-center justify-between gap-2 list-none">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">🧪</span>
                        <div>
                            <h2 class="text-sm font-bold text-slate-300 group-open:text-[#00E5FF] transition">
                                6. Zona de Pruebas y Simulador de Carrera en Vivo (Click para desplegar)
                            </h2>
                            <p class="text-[11px] text-slate-500">
                                Oculto por seguridad para no confundir al operador durante el evento real. Úsalo para ensayar todo el flujo.
                            </p>
                        </div>
                    </div>
                    <span class="font-orbitron text-[10px] px-2.5 py-1 rounded bg-slate-800 text-slate-400 group-open:text-[#00E5FF]">
                        ▼ MOSTRAR / OCULTAR SIMULADOR
                    </span>
                </summary>

                <div class="mt-4 pt-4 border-t border-slate-800 space-y-4">
                    <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs text-amber-200 flex items-center justify-between flex-wrap gap-2">
                        <span>💡 <b>Tip de ensayo:</b> Abre <b>/espectador</b> en otra pestaña o proyector y presiona el botón de <b>Piloto Automático</b> para ver todo el evento de principio a fin.</span>
                        <span id="estado-piloto-texto" class="font-orbitron font-bold text-[#00E5FF]"></span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <button type="button" id="btn-piloto-auto" onclick="iniciarSimulacionCompletaEnVivo()"
                                class="py-3 px-4 rounded-xl bg-gradient-to-r from-[#00E5FF] to-emerald-400 text-[#07101E] font-black text-xs uppercase tracking-wider hover:opacity-90 transition">
                            🎬 1. Piloto Automático (Simular Todo)
                        </button>

                        <form method="POST" action="/admin/simular-equipos">
                            @csrf
                            <button type="submit" class="w-full h-full py-3 px-4 rounded-xl bg-indigo-500/20 border border-indigo-400 text-indigo-200 font-bold text-xs hover:bg-indigo-500/30">
                                🤖 2. Solo Generar 5 Equipos
                            </button>
                        </form>

                        <form method="POST" action="/admin/simular-avance">
                            @csrf
                            <button type="submit" class="w-full h-full py-3 px-4 rounded-xl bg-amber-500/20 border border-amber-400 text-amber-200 font-bold text-xs hover:bg-amber-500/30">
                                🎲 3. Avanzar +1 Pregunta
                            </button>
                        </form>

                        <form method="POST" action="/admin/reiniciar" onsubmit="return confirm('¿Limpiar todos los equipos de prueba y dejar el sistema en Registro listo para el evento real?');">
                            @csrf
                            <input type="hidden" name="borrar_equipos" value="1">
                            <button type="submit" class="w-full h-full py-3 px-4 rounded-xl bg-rose-500/20 border border-rose-500 text-rose-300 font-bold text-xs hover:bg-rose-500/30">
                                🧹 4. Limpiar Simulación (Dejar Cero)
                            </button>
                        </form>
                    </div>
                </div>

                <script>
                    let simulacionActiva = false;

                    async function iniciarSimulacionCompletaEnVivo() {
                        if (simulacionActiva) return;
                        simulacionActiva = true;

                        const btn = document.getElementById('btn-piloto-auto');
                        const status = document.getElementById('estado-piloto-texto');
                        const csrf = '{{ csrf_token() }}';

                        btn.disabled = true;
                        btn.textContent = '⏳ SIMULANDO CARRERA EN VIVO...';

                        try {
                            status.textContent = 'Fase 1: Registrando 5 equipos...';
                            await fetch("/admin/simular-equipos", {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                            });

                            await new Promise(r => setTimeout(r, 2000));

                            let paso = 1;
                            let continuar = true;

                            while (continuar && paso <= 18) {
                                status.textContent = `Corriendo en vivo: Turno #${paso} de 15... (Mira /espectador)`;
                                const res = await fetch("/admin/simular-avance", {
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                                });
                                const data = await res.json();

                                if (!data.alguno_avanzo || data.estado_juego === 'finalizado') {
                                    continuar = false;
                                } else {
                                    paso++;
                                    await new Promise(r => setTimeout(r, 2200));
                                }
                            }

                            status.textContent = '🏆 ¡Carrera simulada completada! Podio activado en /espectador.';
                            setTimeout(() => window.location.reload(), 1500);
                        } catch (e) {
                            console.error(e);
                            status.textContent = 'Error en simulación.';
                            simulacionActiva = false;
                            btn.disabled = false;
                        }
                    }
                </script>
            </details>
        </section>
        @endif

        <script>
            const notyfAdmin = typeof Notyf !== 'undefined'
                ? new Notyf({
                    duration: 3200,
                    position: { x: 'right', y: 'bottom' },
                    dismissible: true,
                    types: [
                        { type: 'success', background: '#0F2342', icon: false },
                        { type: 'error', background: '#7F1D1D', icon: false }
                    ]
                })
                : null;

            function mostrarToast(mensaje, tipo = 'success') {
                if (notyfAdmin) {
                    notyfAdmin.open({
                        type: tipo === 'error' ? 'error' : 'success',
                        message: (tipo === 'error' ? '⚠️ ' : '✅ ') + mensaje
                    });
                }
            }

            @if(session('ok'))
                mostrarToast(@json(session('ok')), 'success');
            @endif
            @if($errors->any())
                mostrarToast(@json($errors->first()), 'error');
            @endif

            function debounceAdmin(fn, wait = 500) {
                let timer = null;
                return (...args) => {
                    clearTimeout(timer);
                    timer = setTimeout(() => fn(...args), wait);
                };
            }

            function actualizarBloqueosUI(bloqueado, estadoJuego) {
                const badge = document.getElementById('badge-estado-admin');
                if (badge && estadoJuego) {
                    badge.textContent = estadoJuego;
                }
                document.querySelectorAll('[data-lockable="true"]').forEach(el => {
                    el.disabled = !!bloqueado;
                });
                ['aviso-bloqueo-config', 'aviso-bloqueo-etapas', 'aviso-bloqueo-banco'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.classList.toggle('hidden', !bloqueado);
                });
            }

            async function autoguardarFormulario(form) {
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                const csrf = csrfMeta ? csrfMeta.content : '';
                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: new FormData(form)
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        const errMsg = data.errors
                            ? Object.values(data.errors)[0][0]
                            : (data.error || data.message || 'No se pudo guardar el cambio.');
                        mostrarToast(errMsg, 'error');
                        return;
                    }
                    mostrarToast(data.mensaje || 'Cambios guardados en vivo.', 'success');
                    if (typeof data.bloqueado === 'boolean') {
                        actualizarBloqueosUI(data.bloqueado, data.estado_juego);
                    }
                } catch (e) {
                    mostrarToast('Error de conexión al guardar en vivo.', 'error');
                }
            }

            function formatearSegundosCalc(totalSeg) {
                const seg = Math.max(0, Math.round(totalSeg));
                const m = Math.floor(seg / 60);
                const s = (seg % 60).toString().padStart(2, '0');
                return `${m}m ${s}s`;
            }

            function recalcularDuracionTorneo() {
                const panel = document.getElementById('calculadora-duracion-torneo');
                if (!panel) return;

                const TRANSICION_SEG = 2;
                let segPromedio = 0;
                let segMaximo = 0;
                let segColchonDesempate = 0;
                const desglose = [];

                document.querySelectorAll('.form-nivel-admin').forEach(formNivel => {
                    const esDesempate = formNivel.dataset.esDesempate === '1';
                    const nombre = (formNivel.querySelector('input[name="nombre"]')?.value || formNivel.dataset.nivelNombre || 'Etapa').trim();
                    const preguntas = Math.max(0, parseInt(formNivel.querySelector('input[name="cantidad_preguntas"]')?.value || '0', 10) || 0);
                    const segundosLimite = Math.max(0, parseInt(formNivel.querySelector('input[name="tiempo_limite_seg"]')?.value || '0', 10) || 0);

                    if (esDesempate) {
                        segColchonDesempate = Math.max(segColchonDesempate, 2 * (segundosLimite + TRANSICION_SEG));
                        return;
                    }

                    const segPuros = preguntas * segundosLimite;
                    const segMaxEtapa = preguntas * (segundosLimite + TRANSICION_SEG);
                    const segPromEtapa = preguntas * ((segundosLimite * 0.60) + TRANSICION_SEG);

                    segMaximo += segMaxEtapa;
                    segPromedio += segPromEtapa;
                    desglose.push({ nombre, preguntas, segundosLimite, segPuros });
                });

                const recomendadoMin = Math.max(1, Math.ceil((segMaximo + segColchonDesempate) / 60));
                const inputTiempoTotal = document.querySelector('#form-configuracion input[name="tiempo_total_minutos"]');
                const tiempoActualMin = Math.max(0, parseInt(inputTiempoTotal?.value || '0', 10) || 0);
                const tiempoActualSeg = tiempoActualMin * 60;

                const elSemaforo = document.getElementById('semaforo-duracion');
                const elDetalle = document.getElementById('semaforo-duracion-detalle');
                const elProm = document.getElementById('calc-ritmo-promedio');
                const elMax = document.getElementById('calc-tiempo-maximo');
                const elRec = document.getElementById('calc-tiempo-recomendado');
                const elDesglose = document.getElementById('calc-desglose-etapas');
                const btnAplicar = document.getElementById('btn-aplicar-tiempo-recomendado');

                if (elProm) elProm.textContent = formatearSegundosCalc(segPromedio);
                if (elMax) elMax.textContent = formatearSegundosCalc(segMaximo);
                if (elRec) elRec.textContent = `${recomendadoMin} min`;

                if (btnAplicar) {
                    btnAplicar.dataset.recomendadoMin = String(recomendadoMin);
                    btnAplicar.textContent = `✨ Aplicar tiempo recomendado (${recomendadoMin} min)`;
                }

                if (elDesglose) {
                    const filas = desglose.map(et => {
                        const fila = document.createElement('div');
                        fila.className = 'flex items-center justify-between gap-2';

                        const izq = document.createElement('span');
                        izq.className = 'truncate text-slate-400';
                        izq.textContent = `${et.nombre} (${et.preguntas} × ${et.segundosLimite}s)`;

                        const der = document.createElement('span');
                        der.className = 'font-mono text-slate-200 shrink-0';
                        der.textContent = formatearSegundosCalc(et.segPuros);

                        fila.append(izq, der);
                        return fila;
                    });
                    elDesglose.replaceChildren(...filas);
                }

                if (elSemaforo && elDetalle) {
                    elSemaforo.className = 'px-2.5 py-0.5 rounded-full border text-[11px] font-bold ';
                    if (tiempoActualSeg < segPromedio) {
                        elSemaforo.className += 'bg-rose-500/20 text-rose-300 border-rose-500/60';
                        elSemaforo.textContent = '⚠️ Tiempo insuficiente';
                        elDetalle.textContent = 'El reloj global cortará la carrera antes de que un equipo promedio termine.';
                    } else if (tiempoActualMin < recomendadoMin) {
                        elSemaforo.className += 'bg-amber-500/20 text-amber-300 border-amber-400/60';
                        elSemaforo.textContent = '⚡ Tiempo ajustado';
                        elDetalle.textContent = 'Cubre el ritmo promedio, pero equipos lentos o un desempate podrían quedar incompletos.';
                    } else {
                        elSemaforo.className += 'bg-emerald-500/20 text-emerald-300 border-emerald-500/60';
                        elSemaforo.textContent = '✅ Duración óptima';
                        elDetalle.textContent = 'Cubre el máximo teórico de todas las etapas más margen para Muerte Súbita.';
                    }
                }
            }

            const formConfig = document.getElementById('form-configuracion');
            if (formConfig) {
                const guardarConfigDebounced = debounceAdmin(() => autoguardarFormulario(formConfig), 500);
                formConfig.querySelectorAll('select').forEach(sel => {
                    sel.addEventListener('change', () => autoguardarFormulario(formConfig));
                });
                formConfig.querySelectorAll('input').forEach(inp => {
                    inp.addEventListener('input', () => {
                        if (inp.name === 'tiempo_total_minutos') {
                            recalcularDuracionTorneo();
                        }
                        guardarConfigDebounced();
                    });
                });
                formConfig.addEventListener('submit', e => {
                    e.preventDefault();
                    autoguardarFormulario(formConfig);
                });

                const btnAplicarRecomendado = document.getElementById('btn-aplicar-tiempo-recomendado');
                const inputTiempoTotal = formConfig.querySelector('input[name="tiempo_total_minutos"]');
                if (btnAplicarRecomendado && inputTiempoTotal) {
                    btnAplicarRecomendado.addEventListener('click', () => {
                        if (btnAplicarRecomendado.disabled || inputTiempoTotal.disabled) return;
                        const minRec = btnAplicarRecomendado.dataset.recomendadoMin;
                        if (!minRec) return;
                        inputTiempoTotal.value = minRec;
                        recalcularDuracionTorneo();
                        autoguardarFormulario(formConfig);
                    });
                }
            }

            document.querySelectorAll('.form-nivel-admin').forEach(formNivel => {
                const guardarNivelDebounced = debounceAdmin(() => autoguardarFormulario(formNivel), 500);
                formNivel.querySelectorAll('input').forEach(inp => {
                    inp.addEventListener('input', () => {
                        recalcularDuracionTorneo();
                        guardarNivelDebounced();
                    });
                });
                formNivel.addEventListener('submit', e => {
                    e.preventDefault();
                    autoguardarFormulario(formNivel);
                });
            });

            recalcularDuracionTorneo();
        </script>
    @endif

</body>
</html>