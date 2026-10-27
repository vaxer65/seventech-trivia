<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seven Tech - Panel de Administración</title>
    @include('partials.cyberpunk-head')
</head>
<body class="min-h-screen p-4 md:p-6">

    @if(!$autorizado)
        <!-- PANTALLA DE LOGIN ADMIN -->
        <div class="max-w-md mx-auto mt-20 card rounded-2xl p-6 shadow-2xl">
            <h1 class="text-2xl font-black text-[#00E5FF] mb-1">🔐 Admin Seven Tech</h1>
            <p class="text-xs text-slate-300 mb-5">Ingresa la clave maestra para controlar la trivia en vivo.</p>

            @if($errors->any())
                <div class="mb-4 p-3 rounded-xl bg-rose-500/20 border border-rose-500 text-rose-200 text-xs font-bold">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}" class="space-y-4">
                @csrf
                <input type="password" name="password" required placeholder="Contraseña de administrador"
                       class="w-full bg-[#07101E] border border-slate-700 rounded-xl px-4 py-3 text-sm text-white outline-none focus:border-[#00E5FF]">
                <button type="submit" class="w-full py-3 rounded-xl bg-[#00E5FF] text-[#07101E] font-black text-sm uppercase">
                    Entrar al Panel de Control
                </button>
            </form>
        </div>
    @else
        <!-- PANEL DE CONTROL COMPLETO -->
        <header class="max-w-7xl mx-auto flex flex-wrap justify-between items-center card rounded-2xl p-4 mb-6">
        <div class="flex items-center gap-3">
            <img src="/favicon.svg" alt="Seven Tech" class="w-11 h-11 rounded-xl">
            <div>
                <h1 class="text-xl font-black text-[#00E5FF]">CENTRO DE MANDO SEVEN TECH</h1>
                <p class="text-xs text-slate-300">Estado actual: <b class="uppercase text-amber-400">{{ $config->estado_juego }}</b></p>
            </div>
        </div>
        </header>

        @if(session('ok'))
            <div class="max-w-7xl mx-auto mb-6 p-3.5 rounded-xl bg-emerald-500/20 border border-emerald-400 text-emerald-200 text-sm font-bold">
                ✅ {{ session('ok') }}
            </div>
        @endif

        <main class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- 1. CONTROL DE ESTADO DEL EVENTO Y DESEMPATE -->
            <section class="card rounded-2xl p-5 space-y-5">
                <h2 class="text-base font-extrabold text-[#00E5FF] border-b border-slate-800 pb-2">1. Control de Carrera en Vivo</h2>

                <form method="POST" action="{{ route('admin.config') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="text-xs font-bold text-slate-300">Estado de la Trivia</label>
                        <select name="estado_juego" class="w-full mt-1 bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2.5 text-sm">
                            <option value="registro" {{ $config->estado_juego == 'registro' ? 'selected' : '' }}>🟢 Registro Abierto (En espera)</option>
                            <option value="en_curso" {{ $config->estado_juego == 'en_curso' ? 'selected' : '' }}>🚀 EN CURSO (¡Corriendo Carrera!)</option>
                            <option value="pausado" {{ $config->estado_juego == 'pausado' ? 'selected' : '' }}>⏸️ Pausado</option>
                            <option value="finalizado" {{ $config->estado_juego == 'finalizado' ? 'selected' : '' }}>🏁 Finalizado</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-300">Módulo / Categoría Activa</label>
                        <select name="modulo_activo_id" class="w-full mt-1 bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2.5 text-sm">
                            @foreach($modulos as $mod)
                                <option value="{{ $mod->id }}" {{ $config->modulo_activo_id == $mod->id ? 'selected' : '' }}>
                                    {{ $mod->nombre }} ({{ $mod->preguntas_count }} pregs)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs text-slate-400">Máx. Integrantes</label>
                            <input type="number" name="max_integrantes_equipo" value="{{ $config->max_integrantes_equipo }}" class="w-full bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-slate-400">Máx. Equipos</label>
                            <input type="number" name="max_equipos_permitidos" value="{{ $config->max_equipos_permitidos }}" class="w-full bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2 text-sm">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-2.5 rounded-xl bg-[#00E5FF] text-[#07101E] font-black text-xs uppercase">
                        Guardar y Aplicar en Vivo
                    </button>
                </form>

                <!-- Botón de Muerte Súbita (Desempate) -->
                <div class="pt-3 border-t border-slate-800">
                    <h3 class="text-xs font-bold uppercase text-rose-400 mb-2">🔥 Sistema de Desempate</h3>
                    <form method="POST" action="{{ route('admin.desempate') }}">
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

                <!-- Reiniciar Juego -->
                <div class="pt-3 border-t border-slate-800">
                    <form method="POST" action="{{ route('admin.reiniciar') }}" onsubmit="return confirm('¿Seguro que deseas reiniciar los puntajes de la partida?');">
                        @csrf
                        <label class="flex items-center gap-2 text-xs text-slate-300 mb-2">
                            <input type="checkbox" name="borrar_equipos" value="1"> Eliminar también todos los equipos registrados
                        </label>
                        <button type="submit" class="w-full py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs">
                            🔄 Reiniciar Partida
                        </button>
                    </form>
                </div>
                <div class="pt-3 border-t border-slate-800 space-y-2">
                    <h3 class="text-xs font-bold uppercase text-[#00E5FF]">🧪 Simulador de Evento (Pruebas)</h3>
                    <form method="POST" action="{{ route('admin.simular.equipos') }}">
                        @csrf
                        <button type="submit" class="w-full py-2 rounded-xl bg-indigo-500/20 border border-indigo-400 text-indigo-200 font-bold text-xs hover:bg-indigo-500/30">
                            🤖 1. Generar 5 Equipos de Prueba
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.simular.avance') }}">
                        @csrf
                        <button type="submit" class="w-full py-2 rounded-xl bg-amber-500/20 border border-amber-400 text-amber-200 font-bold text-xs hover:bg-amber-500/30">
                            🎲 2. Simular Avance de Carrera (+1 Pregunta)
                        </button>
                    </form>
                </div>
            </section>

            <!-- 2. ESCALABILIDAD: EDITAR ETAPAS Y CREAR MÓDULOS -->
            <section class="card rounded-2xl p-5 space-y-5">
                <h2 class="text-base font-extrabold text-[#00E5FF] border-b border-slate-800 pb-2">2. Configurar Etapas de la Carrera</h2>
                <div class="space-y-3">
                    @foreach($niveles as $niv)
                        <form method="POST" action="{{ route('admin.nivel', $niv->id) }}" class="bg-[#07101E] p-3 rounded-xl border border-slate-800 space-y-2">
                            @csrf
                            <input type="text" name="nombre" value="{{ $niv->nombre }}" class="w-full bg-transparent font-bold text-xs text-[#00E5FF] border-b border-slate-800 pb-1">
                            <div class="grid grid-cols-3 gap-2 text-xs">
                                <div>
                                    <span class="text-[10px] text-slate-400">Preguntas</span>
                                    <input type="number" name="cantidad_preguntas" value="{{ $niv->cantidad_preguntas }}" class="w-full bg-[#0F2342] rounded px-2 py-1">
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-400">Puntos c/u</span>
                                    <input type="number" name="puntos_por_acierto" value="{{ $niv->puntos_por_acierto }}" class="w-full bg-[#0F2342] rounded px-2 py-1">
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-400">Segundos</span>
                                    <input type="number" name="tiempo_limite_seg" value="{{ $niv->tiempo_limite_seg }}" class="w-full bg-[#0F2342] rounded px-2 py-1">
                                </div>
                            </div>
                            <button class="w-full py-1.5 rounded-lg bg-cyan-500/20 text-[#00E5FF] font-bold text-xs">Actualizar Etapa</button>
                        </form>
                    @endforeach
                </div>

                <!-- Crear Nuevo Módulo -->
                <div class="pt-3 border-t border-slate-800">
                    <h3 class="text-xs font-bold uppercase text-[#00E5FF] mb-2">➕ Agregar Nuevo Módulo/Categoría</h3>
                    <form method="POST" action="{{ route('admin.modulo') }}" class="space-y-2">
                        @csrf
                        <input type="text" name="nombre" required placeholder="Nombre del módulo (Ej: Robótica e IoT)" class="w-full bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2 text-xs">
                        <input type="text" name="descripcion" placeholder="Descripción corta" class="w-full bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2 text-xs">
                        <button class="w-full py-2 rounded-xl bg-emerald-500 text-[#07101E] font-black text-xs uppercase">Crear Módulo</button>
                    </form>
                </div>
            </section>

            <!-- 3. AGREGAR NUEVAS PREGUNTAS AL BANCO -->
            <section class="card rounded-2xl p-5 space-y-4">
                <h2 class="text-base font-extrabold text-[#00E5FF] border-b border-slate-800 pb-2">3. Añadir Pregunta al Banco</h2>
                <form method="POST" action="{{ route('admin.pregunta') }}" class="space-y-2.5 text-xs">
                    @csrf
                    <div>
                        <label class="text-slate-300 font-bold">Módulo</label>
                        <select name="modulo_id" class="w-full mt-1 bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2">
                            @foreach($modulos as $m)
                                <option value="{{ $m->id }}">{{ $m->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-slate-300 font-bold">Etapa / Nivel de Dificultad</label>
                        <select name="nivel_id" class="w-full mt-1 bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2">
                            @foreach($niveles as $n)
                                <option value="{{ $n->id }}">{{ $n->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-slate-300 font-bold">Enunciado de la Pregunta</label>
                        <textarea name="enunciado" required rows="2" class="w-full mt-1 bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2"></textarea>
                    </div>
                    <input type="text" name="opcion_a" required placeholder="Opción A" class="w-full bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2">
                    <input type="text" name="opcion_b" required placeholder="Opción B" class="w-full bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2">
                    <input type="text" name="opcion_c" required placeholder="Opción C" class="w-full bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2">
                    <input type="text" name="opcion_d" required placeholder="Opción D" class="w-full bg-[#07101E] border border-slate-700 rounded-xl px-3 py-2">
                    <div>
                        <label class="text-emerald-400 font-bold">Respuesta Correcta (Secreta)</label>
                        <select name="respuesta_correcta" class="w-full mt-1 bg-[#07101E] border border-emerald-500 rounded-xl px-3 py-2 font-bold text-emerald-300">
                            <option value="a">Opción A</option>
                            <option value="b">Opción B</option>
                            <option value="c">Opción C</option>
                            <option value="d">Opción D</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full py-2.5 rounded-xl bg-[#00E5FF] text-[#07101E] font-black uppercase">
                        Guardar Pregunta
                    </button>
                </form>
            </section>

        </main>
        <!-- 4. GESTOR COMPLETO DEL BANCO DE PREGUNTAS (ORDENADO POR MÓDULO Y ETAPA 1, 2, 3, DESEMPATE) -->
        <section class="max-w-7xl mx-auto mt-6 card rounded-2xl p-5">
            <div class="flex flex-wrap justify-between items-center border-b border-slate-800 pb-3 mb-4 gap-3">
                <div>
                    <h2 class="text-lg font-extrabold text-[#00E5FF]">4. Banco de Preguntas Registradas ({{ $preguntas->count() }})</h2>
                    <p class="text-xs text-slate-400">Ordenadas por Módulo y Etapa (Fase 1 → Fase 2 → Fase 3 → Desempate).</p>
                </div>

                <!-- Filtros rápidos para encontrar preguntas al instante -->
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
                                <span class="text-white font-semibold">{{ $preg->enunciado }}</span>
                            </div>

                            <!-- Ahora el texto queda fijo a la derecha y SOLO gira el ícono de la flecha -->
                            <div class="shrink-0 flex items-center gap-1.5 text-xs text-[#00E5FF] bg-[#0F2342] px-2.5 py-1 rounded-lg border border-[#00E5FF]/30">
                                <span>Editar / Borrar</span>
                                <span class="inline-block transition-transform duration-200 group-open:rotate-180">▼</span>
                            </div>
                        </summary>

                        <div class="mt-3 pt-3 border-t border-slate-800">
                            <form method="POST" action="{{ route('admin.pregunta.editar', $preg->id) }}" class="space-y-2 text-xs">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-slate-400">Módulo</label>
                                        <select name="modulo_id" class="w-full bg-[#0F2342] border border-slate-700 rounded-lg px-2 py-1.5">
                                            @foreach($modulos as $m)
                                                <option value="{{ $m->id }}" {{ $preg->modulo_id == $m->id ? 'selected' : '' }}>{{ $m->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-slate-400">Etapa</label>
                                        <select name="nivel_id" class="w-full bg-[#0F2342] border border-slate-700 rounded-lg px-2 py-1.5">
                                            @foreach($niveles as $n)
                                                <option value="{{ $n->id }}" {{ $preg->nivel_id == $n->id ? 'selected' : '' }}>{{ $n->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <textarea name="enunciado" rows="2" class="w-full bg-[#0F2342] border border-slate-700 rounded-lg px-3 py-2 text-white">{{ $preg->enunciado }}</textarea>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <input type="text" name="opcion_a" value="{{ $preg->opcion_a }}" class="bg-[#0F2342] border border-slate-700 rounded-lg px-2.5 py-1.5">
                                    <input type="text" name="opcion_b" value="{{ $preg->opcion_b }}" class="bg-[#0F2342] border border-slate-700 rounded-lg px-2.5 py-1.5">
                                    <input type="text" name="opcion_c" value="{{ $preg->opcion_c }}" class="bg-[#0F2342] border border-slate-700 rounded-lg px-2.5 py-1.5">
                                    <input type="text" name="opcion_d" value="{{ $preg->opcion_d }}" class="bg-[#0F2342] border border-slate-700 rounded-lg px-2.5 py-1.5">
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-2 pt-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-emerald-400 font-bold">Correcta:</span>
                                        <select name="respuesta_correcta" class="bg-[#0F2342] border border-emerald-500 rounded-lg px-2.5 py-1 font-bold text-emerald-300">
                                            <option value="a" {{ $preg->getRawOriginal('respuesta_correcta') == 'a' ? 'selected' : '' }}>Opción A</option>
                                            <option value="b" {{ $preg->getRawOriginal('respuesta_correcta') == 'b' ? 'selected' : '' }}>Opción B</option>
                                            <option value="c" {{ $preg->getRawOriginal('respuesta_correcta') == 'c' ? 'selected' : '' }}>Opción C</option>
                                            <option value="d" {{ $preg->getRawOriginal('respuesta_correcta') == 'd' ? 'selected' : '' }}>Opción D</option>
                                        </select>
                                    </div>

                                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-[#00E5FF] text-[#07101E] font-bold uppercase">
                                        💾 Guardar Cambios
                                    </button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('admin.pregunta.eliminar', $preg->id) }}" class="mt-2 text-right" onsubmit="return confirm('¿Eliminar esta pregunta definitivamente?');">
                                @csrf
                                <button type="submit" class="px-3 py-1 rounded-lg bg-rose-500/20 border border-rose-500 text-rose-300 text-xs font-bold hover:bg-rose-500/30">
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
    @endif

</body>
</html>