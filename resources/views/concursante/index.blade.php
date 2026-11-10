<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Seven Tech Trivia - Jugar</title>
    @include('partials.cyberpunk-head')
    <style>
        body {
            user-select: none;
            -webkit-user-select: none;
            -webkit-touch-callout: none;
        }
    </style>
</head>
<body class="circuit-bg min-h-screen flex flex-col justify-between p-3 sm:p-5 relative overflow-x-hidden"
      oncontextmenu="return false;" oncopy="return false;" oncut="return false;" onpaste="return false;">

    <!-- ESCUDO ANTI-IA / ANTI-CAPTURAS -->
    <div id="cortina-anti-ia" class="hidden fixed inset-0 z-50 bg-[#07101E]/98 backdrop-blur-xl flex flex-col items-center justify-center p-6 text-center">
        <div class="text-6xl mb-4 animate-bounce">🛡️</div>
        <h2 class="cyber-title text-2xl sm:text-3xl text-rose-500 mb-2">ALERTA DE SEGURIDAD ANTI-IA</h2>
        <p class="text-slate-300 text-sm max-w-md mb-6">
            Se detectó que saliste de la pantalla de competencia o intentaste abrir otra aplicación/captura. Las preguntas se ocultan automáticamente por seguridad.
        </p>
        <button onclick="desactivarCortina()" class="px-6 py-3 rounded-xl bg-[#00E5FF] text-[#07101E] font-orbitron font-bold text-xs uppercase tracking-wider">
            VOLVER A LA COMPETENCIA
        </button>
    </div>

    <!-- CABECERA DEL CONCURSANTE -->
    <header class="w-full max-w-lg mx-auto neon-border rounded-2xl p-3.5 flex items-center justify-between bg-[#0F2342]/90">
        <a href="/" title="Volver al Portal Principal" class="flex items-center gap-3 hover:opacity-90 transition">
            <img src="/favicon.svg" alt="Seven Tech" class="w-10 h-10 rounded-xl border border-[#00E5FF]">
            <div>
                <h1 class="cyber-title text-lg sm:text-xl leading-none">SEVEN TECH</h1>
                <p class="font-orbitron text-[10px] text-slate-300 mt-1">Seven Tech Trivia 2026</p>
            </div>
        </a>

        <!-- Información del equipo activo + Reloj Global + Botón Salir -->
        <div id="info-equipo-header" class="hidden flex items-center gap-2">
            <div class="text-center bg-[#07101E] px-2.5 py-1 rounded-lg border border-slate-700">
                <span class="font-orbitron block text-[8px] text-slate-400 uppercase">Torneo</span>
                <span id="reloj-global-movil" class="font-orbitron text-xs font-bold text-amber-400">--:--</span>
            </div>
            <div class="text-right bg-[#07101E] px-3 py-1 rounded-lg border border-[#00E5FF]/40">
                <p id="header-nombre-equipo" class="text-xs font-bold text-white leading-tight"></p>
                <p id="header-puntaje" class="font-orbitron text-xs text-[#00E5FF] font-bold">0 PTS</p>
            </div>
            <button type="button" onclick="cerrarSesionEquipo()" title="Salir / Regresar al inicio"
                    class="p-2 rounded-lg bg-slate-800/90 hover:bg-rose-500/20 border border-slate-700 hover:border-rose-500 text-xs text-slate-300 hover:text-rose-300 transition">
                🚪
            </button>
        </div>
    </header>

    <!-- CONTENEDOR CENTRAL DINÁMICO -->
    <main class="w-full max-w-lg mx-auto my-auto py-4">

        <!-- 1. PANTALLA DE REGISTRO O RECUPERACIÓN CON PIN -->
        <section id="vista-registro" class="hidden neon-border rounded-2xl p-5 bg-[#0F2342]/90 space-y-4">
            <div class="grid grid-cols-2 gap-2 bg-[#07101E] p-1 rounded-xl border border-slate-800">
                <button type="button" id="tab-nuevo" onclick="cambiarTab('nuevo')"
                        class="py-2 rounded-lg font-orbitron text-xs font-bold bg-[#00E5FF] text-[#07101E] transition">
                    🚀 NUEVO EQUIPO
                </button>
                <button type="button" id="tab-pin" onclick="cambiarTab('pin')"
                        class="py-2 rounded-lg font-orbitron text-xs font-bold text-slate-400 hover:text-white transition">
                    🔑 USAR PIN
                </button>
            </div>

            <div id="alerta-error" class="hidden p-3 rounded-xl bg-rose-500/20 border border-rose-500 text-rose-200 text-xs font-semibold"></div>

            <!-- Formulario Nuevo Equipo -->
            <form id="form-registro" class="space-y-4">
                <div>
                    <label class="font-orbitron text-[11px] text-[#00E5FF] uppercase font-bold">Nombre del Equipo *</label>
                    <input type="text" name="nombre_equipo" required maxlength="40" placeholder="Ej: CyberTitans"
                           class="w-full mt-1 bg-[#07101E] border border-slate-700 focus:border-[#00E5FF] rounded-xl px-3.5 py-2.5 text-base text-white outline-none">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="font-orbitron text-[11px] text-[#00E5FF] uppercase font-bold">
                            Integrantes (Mín 2 / Máx {{ $config->max_integrantes_equipo ?? 5 }}) *
                        </label>
                        <button type="button" onclick="agregarCampoIntegrante()"
                                class="text-xs px-2.5 py-1 rounded-lg bg-cyan-500/15 border border-[#00E5FF]/50 text-[#00E5FF] font-bold">
                            + Añadir integrante
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-400 mb-2">⚠️ Ingresa el <b>Nombre y Apellido real</b> de cada participante (son privados y solo visibles para el jurado/administrador).</p>

                    <div id="lista-integrantes" class="space-y-2">
                        <input type="text" name="integrantes[]" required placeholder="Nombre y Apellido del Capitán *"
                               class="w-full bg-[#07101E] border border-slate-700 focus:border-[#00E5FF] rounded-xl px-3.5 py-2.5 text-base text-white outline-none">
                        <input type="text" name="integrantes[]" required placeholder="Nombre y Apellido del Integrante 2 *"
                               class="w-full bg-[#07101E] border border-slate-700 focus:border-[#00E5FF] rounded-xl px-3.5 py-2.5 text-base text-white outline-none">
                    </div>
                </div>

                <button type="submit" id="btn-registrar"
                        class="w-full py-3.5 rounded-xl bg-[#00E5FF] hover:bg-cyan-300 text-[#07101E] font-orbitron font-bold text-xs uppercase tracking-wider shadow-[0_0_20px_rgba(0,229,255,0.4)] transition">
                    🚀 REGISTRAR EQUIPO Y GENERAR PIN
                </button>
            </form>

            <!-- Formulario Recuperar Sesión con PIN -->
            <form id="form-recuperar" class="hidden space-y-4">
                <p class="text-xs text-slate-300">Si se cerró tu navegador o cambiaste de teléfono, ingresa el nombre de tu equipo y tu PIN de 4 dígitos:</p>
                <div>
                    <label class="font-orbitron text-[11px] text-[#00E5FF] uppercase font-bold">Nombre Exacto del Equipo</label>
                    <input type="text" name="nombre_equipo" required placeholder="Ej: CyberTitans"
                           class="w-full mt-1 bg-[#07101E] border border-slate-700 focus:border-[#00E5FF] rounded-xl px-3.5 py-2.5 text-base text-white outline-none">
                </div>
                <div>
                    <label class="font-orbitron text-[11px] text-amber-400 uppercase font-bold">🔑 PIN de Recuperación (4 dígitos)</label>
                    <input type="text" name="pin_recuperacion" required maxlength="4" placeholder="Ej: 4829"
                           class="w-full mt-1 bg-[#07101E] border border-amber-400/60 rounded-xl px-3.5 py-2.5 font-orbitron text-lg tracking-widest text-amber-300 text-center outline-none">
                </div>
                <button type="submit"
                        class="w-full py-3.5 rounded-xl bg-amber-400 text-[#07101E] font-orbitron font-bold text-xs uppercase tracking-wider">
                    🔄 RECUPERAR MI CARRERA
                </button>
            </form>

            <div class="pt-2 border-t border-slate-800 text-center">
                <a href="/" class="text-xs text-slate-400 hover:text-[#00E5FF] font-orbitron">← Volver al Portal Principal</a>
            </div>
        </section>

        <!-- 2. SALA DE ESPERA / PAUSA -->
        <section id="vista-espera" class="hidden neon-border rounded-2xl p-6 bg-[#0F2342]/90 text-center space-y-4">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-cyan-500/10 border border-[#00E5FF] flex items-center justify-center text-3xl animate-pulse">
                🤖
            </div>
            <h2 id="titulo-espera" class="cyber-title text-2xl">🚀 EN LÍNEA DE SALIDA</h2>
            <p id="subtitulo-espera" class="text-slate-300 text-sm">
                Tu equipo está listo. La carrera comenzará en breves instantes.
            </p>

            <!-- Caja destacada con el PIN de recuperación -->
            <div class="p-3.5 rounded-xl bg-[#07101E] border border-amber-400/50">
                <p class="font-orbitron text-[10px] text-amber-300 uppercase">🔑 PIN de Respaldo del Equipo (Guárdalo)</p>
                <p id="pin-equipo-mostrar" class="font-orbitron text-2xl font-bold text-amber-400 tracking-widest mt-1">----</p>
                <p class="text-[11px] text-slate-400 mt-1">Úsalo si necesitas cambiar de dispositivo durante el evento.</p>
            </div>

            <div class="pt-2 flex flex-wrap items-center justify-center gap-3">
                <button type="button" onclick="cerrarSesionEquipo()"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-orbitron text-[11px] border border-slate-700 transition">
                    🔄 Cambiar de Equipo / Ir al Registro
                </button>
                <a href="/" class="px-4 py-2 rounded-xl bg-[#07101E] hover:border-[#00E5FF] text-[#00E5FF] font-orbitron text-[11px] border border-slate-800 transition">
                    🏠 Portal Principal
                </a>
            </div>
        </section>

        <!-- 3. PANTALLA DE PREGUNTA ACTIVA -->
        <section id="vista-pregunta" class="hidden neon-border rounded-2xl p-5 bg-[#0F2342]/95 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-2.5">
                <div>
                    <span id="badge-nivel" class="font-orbitron text-[10px] font-bold uppercase px-2.5 py-1 rounded bg-cyan-500/20 text-[#00E5FF] border border-[#00E5FF]/50">
                        ETAPA 1
                    </span>
                    <span id="progreso-fase" class="font-orbitron text-[10px] text-slate-400 ml-2">1 / 5</span>
                </div>
                <div class="flex items-center gap-1.5 bg-[#07101E] px-3 py-1 rounded-xl border border-slate-700">
                    <span class="text-xs">⏱️</span>
                    <span id="reloj-pregunta" class="font-orbitron text-base font-bold text-[#00E5FF]">25s</span>
                </div>
            </div>

            <!-- Barra de tiempo visual -->
            <div class="w-full h-2 bg-[#07101E] rounded-full overflow-hidden border border-slate-800">
                <div id="barra-tiempo" class="h-full bg-[#00E5FF] transition-all duration-300" style="width: 100%;"></div>
            </div>

            <h3 id="texto-enunciado" class="text-lg sm:text-xl font-bold text-white leading-snug py-1"></h3>

            <div id="contenedor-opciones" class="space-y-2.5 pt-1"></div>

            <div id="feedback-respuesta" class="hidden p-3 rounded-xl text-center font-orbitron text-xs font-bold"></div>
        </section>

        <!-- 4. PANTALLA DE BANDERITA: META CRUZADA / EVENTO FINALIZADO -->
        <section id="vista-final" class="hidden neon-border rounded-2xl p-6 bg-[#0F2342]/95 text-center space-y-4">
            <div id="icono-meta" class="text-6xl animate-bounce">🏁</div>
            <h2 id="titulo-meta" class="cyber-title text-2xl sm:text-3xl text-[#00E5FF]">¡CRUZASTE LA META!</h2>
            <p id="subtitulo-meta" class="text-slate-300 text-sm">
                Tu equipo ha completado la carrera de <b>Seven Tech</b>. Mira la pantalla gigante del auditorio para conocer el podio oficial.
            </p>

            <div class="grid grid-cols-2 gap-3 bg-[#07101E] p-4 rounded-xl border border-[#00E5FF]/40">
                <div>
                    <p class="font-orbitron text-[10px] text-slate-400 uppercase">Puntaje Final</p>
                    <p id="puntaje-final-texto" class="font-orbitron text-2xl sm:text-3xl font-bold text-emerald-400 mt-1">0 PTS</p>
                </div>
                <div class="border-l border-slate-800">
                    <p class="font-orbitron text-[10px] text-slate-400 uppercase">Aciertos</p>
                    <p id="aciertos-final-texto" class="font-orbitron text-2xl sm:text-3xl font-bold text-[#00E5FF] mt-1">0</p>
                </div>
            </div>

            <div class="p-3 rounded-xl bg-[#07101E]/70 border border-slate-800 text-xs text-slate-400">
                🔑 PIN de tu equipo: <b id="pin-final-texto" class="font-orbitron text-amber-400">----</b>
            </div>

            <div class="pt-2 flex flex-wrap items-center justify-center gap-3">
                <button type="button" onclick="cerrarSesionEquipo()"
                        class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-orbitron text-xs font-bold border border-slate-700 transition">
                    🔄 Registrar o Entrar con Otro Equipo
                </button>
                <a href="/" class="px-4 py-2.5 rounded-xl bg-[#00E5FF]/20 border border-[#00E5FF] text-[#00E5FF] font-orbitron text-xs font-bold transition">
                    🏠 Ir al Inicio
                </a>
            </div>
        </section>

    </main>

    <footer class="text-center font-orbitron text-[10px] text-slate-500 py-2">
        Seven Tech Community • Protección Anti-IA Activa
    </footer>

    <script>
        const maxIntegrantes = {{ $config->max_integrantes_equipo ?? 5 }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        let preguntaActualId = null;
        let inicioPreguntaTimestamp = null;
        let timerInterval = null;
        let tiempoLimiteActual = 25;
        let enviandoRespuesta = false;
        let enPreguntaActiva = false;
        let segundosGlobalesMovil = null;

        setInterval(() => {
            if (segundosGlobalesMovil !== null && segundosGlobalesMovil > 0) {
                segundosGlobalesMovil--;
                const m = Math.floor(segundosGlobalesMovil / 60).toString().padStart(2, '0');
                const s = Math.floor(segundosGlobalesMovil % 60).toString().padStart(2, '0');
                const el = document.getElementById('reloj-global-movil');
                if (el) el.textContent = `${m}:${s}`;
            }
        }, 1000);

        function mostrarVista(idVista) {
            ['vista-registro', 'vista-espera', 'vista-pregunta', 'vista-final'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.toggle('hidden', id !== idVista);
            });
            enPreguntaActiva = (idVista === 'vista-pregunta');
        }

        async function cerrarSesionEquipo() {
            if (!confirm('¿Deseas salir de este equipo y regresar a la pantalla de registro inicial? (Podrás volver a entrar luego usando tu PIN).')) {
                return;
            }
            try {
                await fetch('/api/salir-equipo', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
                });
            } catch (e) {}
            clearInterval(timerInterval);
            preguntaActualId = null;
            sincronizarJuego();
        }

        function cambiarTab(tab) {
            const esNuevo = (tab === 'nuevo');
            document.getElementById('form-registro').classList.toggle('hidden', !esNuevo);
            document.getElementById('form-recuperar').classList.toggle('hidden', esNuevo);
            document.getElementById('tab-nuevo').className = esNuevo
                ? 'py-2 rounded-lg font-orbitron text-xs font-bold bg-[#00E5FF] text-[#07101E] transition'
                : 'py-2 rounded-lg font-orbitron text-xs font-bold text-slate-400 hover:text-white transition';
            document.getElementById('tab-pin').className = !esNuevo
                ? 'py-2 rounded-lg font-orbitron text-xs font-bold bg-amber-400 text-[#07101E] transition'
                : 'py-2 rounded-lg font-orbitron text-xs font-bold text-slate-400 hover:text-white transition';
            document.getElementById('alerta-error').classList.add('hidden');
        }

        function agregarCampoIntegrante() {
            const contenedor = document.getElementById('lista-integrantes');
            const total = contenedor.querySelectorAll('input').length;
            if (total >= maxIntegrantes) {
                alert('El máximo permitido es de ' + maxIntegrantes + ' integrantes por equipo.');
                return;
            }
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'integrantes[]';
            input.placeholder = `Nombre y Apellido del Integrante ${total + 1} *`;
            input.className = 'w-full bg-[#07101E] border border-slate-700 focus:border-[#00E5FF] rounded-xl px-3.5 py-2.5 text-base text-white outline-none';
            contenedor.appendChild(input);
        }

        document.getElementById('form-registro').addEventListener('submit', async (e) => {
            e.preventDefault();
            const alerta = document.getElementById('alerta-error');
            alerta.classList.add('hidden');

            const formData = new FormData(e.target);
            const payload = {
                nombre_equipo: formData.get('nombre_equipo'),
                integrantes: formData.getAll('integrantes[]').filter(v => v.trim() !== '')
            };

            try {
                const res = await fetch('/api/registrar-equipo', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!res.ok) {
                    const primerError = data.errors ? Object.values(data.errors)[0][0] : (data.error || data.message);
                    alerta.textContent = primerError || 'Verifica los datos ingresados.';
                    alerta.classList.remove('hidden');
                    return;
                }
                sincronizarJuego();
            } catch (err) {
                alerta.textContent = 'Error de conexión. Intenta nuevamente.';
                alerta.classList.remove('hidden');
            }
        });

        document.getElementById('form-recuperar').addEventListener('submit', async (e) => {
            e.preventDefault();
            const alerta = document.getElementById('alerta-error');
            alerta.classList.add('hidden');

            const formData = new FormData(e.target);
            try {
                const res = await fetch('/api/recuperar-sesion', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        nombre_equipo: formData.get('nombre_equipo'),
                        pin_recuperacion: formData.get('pin_recuperacion')
                    })
                });
                const data = await res.json();
                if (!res.ok) {
                    alerta.textContent = data.error || 'No se pudo recuperar el equipo.';
                    alerta.classList.remove('hidden');
                    return;
                }
                sincronizarJuego();
            } catch (err) {
                alerta.textContent = 'Error al conectar con el servidor.';
                alerta.classList.remove('hidden');
            }
        });

        async function sincronizarJuego() {
            if (enviandoRespuesta) return;

            try {
                const res = await fetch('/api/estado-juego');
                const data = await res.json();

                if (!data.autenticado || !data.equipo) {
                    document.getElementById('info-equipo-header').classList.add('hidden');
                    mostrarVista('vista-registro');
                    return;
                }

                const headerEquipo = document.getElementById('info-equipo-header');
                headerEquipo.classList.remove('hidden');
                document.getElementById('header-nombre-equipo').textContent = data.equipo.nombre_equipo;
                document.getElementById('header-puntaje').textContent = `${data.equipo.puntaje_total} PTS`;

                const elRelojMovil = document.getElementById('reloj-global-movil');
                if (elRelojMovil) {
                    if (data.segundos_globales !== null && data.segundos_globales !== undefined) {
                        segundosGlobalesMovil = data.segundos_globales;
                        const m = Math.floor(segundosGlobalesMovil / 60).toString().padStart(2, '0');
                        const s = Math.floor(segundosGlobalesMovil % 60).toString().padStart(2, '0');
                        elRelojMovil.textContent = `${m}:${s}`;
                    } else {
                        segundosGlobalesMovil = null;
                        elRelojMovil.textContent = (data.estado_juego || '--').toUpperCase();
                    }
                }

                if (data.equipo.pin_recuperacion) {
                    document.getElementById('pin-equipo-mostrar').textContent = data.equipo.pin_recuperacion;
                    document.getElementById('pin-final-texto').textContent = data.equipo.pin_recuperacion;
                }

                if (data.estado_juego === 'carrera_completada' || data.estado_juego === 'finalizado' || data.equipo.finalizo_carrera) {
                    clearInterval(timerInterval);
                    preguntaActualId = null;
                    document.getElementById('puntaje-final-texto').textContent = `${data.equipo.puntaje_total} PTS`;
                    document.getElementById('aciertos-final-texto').textContent = `${data.equipo.aciertos_totales ?? 0}`;
                    document.getElementById('titulo-meta').textContent = data.estado_juego === 'finalizado'
                        ? '¡TORNEO FINALIZADO!'
                        : '¡CRUZASTE LA META!';
                    mostrarVista('vista-final');
                    return;
                }

                if (['registro', 'pausado', 'esperando_desempate'].includes(data.estado_juego)) {
                    clearInterval(timerInterval);
                    preguntaActualId = null;
                    const tit = document.getElementById('titulo-espera');
                    const sub = document.getElementById('subtitulo-espera');

                    if (data.estado_juego === 'pausado') {
                        tit.textContent = '⏸️ CARRERA EN PAUSA';
                        sub.textContent = 'El administrador ha pausado momentáneamente la partida. Espera indicaciones.';
                    } else if (data.estado_juego === 'esperando_desempate') {
                        tit.textContent = '🔥 MUERTE SÚBITA EN CURSO';
                        sub.textContent = 'Los equipos empatados están definiendo el podio en la pantalla gigante.';
                    } else {
                        tit.textContent = '🚀 EN LÍNEA DE SALIDA';
                        sub.textContent = 'Tu equipo está listo. La carrera comenzará en breves instantes.';
                    }
                    mostrarVista('vista-espera');
                    return;
                }

                if (data.pregunta) {
                    mostrarVista('vista-pregunta');
                    if (preguntaActualId !== data.pregunta.id) {
                        renderizarNuevaPregunta(data.pregunta, data.nivel, data.progreso_fase);
                    }
                }
            } catch (e) {
                console.error('Error sincronizando estado:', e);
            }
        }

        function renderizarNuevaPregunta(pregunta, nivel, progreso) {
            preguntaActualId = pregunta.id;
            inicioPreguntaTimestamp = Date.now();
            tiempoLimiteActual = nivel ? nivel.tiempo_limite_seg : 25;

            document.getElementById('feedback-respuesta').classList.add('hidden');
            document.getElementById('badge-nivel').textContent = nivel ? nivel.nombre : 'ETAPA ACTIVA';
            document.getElementById('progreso-fase').textContent = progreso || '';
            document.getElementById('texto-enunciado').textContent = pregunta.enunciado;

            const opciones = [
                { letra: 'a', texto: pregunta.opcion_a },
                { letra: 'b', texto: pregunta.opcion_b },
                { letra: 'c', texto: pregunta.opcion_c },
                { letra: 'd', texto: pregunta.opcion_d },
            ];

            const contenedor = document.getElementById('contenedor-opciones');
            contenedor.innerHTML = opciones.map(op => `
                <button type="button" onclick="enviarRespuesta('${op.letra}', this)"
                        class="btn-opcion w-full text-left p-3.5 rounded-xl bg-[#07101E] hover:bg-[#00E5FF]/15 border border-slate-700 hover:border-[#00E5FF] transition flex items-center gap-3">
                    <span class="font-orbitron text-xs font-bold w-7 h-7 rounded-lg bg-[#0F2342] border border-[#00E5FF]/50 text-[#00E5FF] flex items-center justify-center uppercase shrink-0">
                        ${op.letra}
                    </span>
                    <span class="text-sm sm:text-base font-semibold text-white">${op.texto}</span>
                </button>
            `).join('');

            iniciarCronometroPregunta(tiempoLimiteActual);
        }

        function iniciarCronometroPregunta(segundosTotales) {
            clearInterval(timerInterval);
            let restantes = segundosTotales;
            const elReloj = document.getElementById('reloj-pregunta');
            const elBarra = document.getElementById('barra-tiempo');

            elReloj.textContent = `${restantes}s`;
            elBarra.style.width = '100%';

            timerInterval = setInterval(() => {
                restantes--;
                elReloj.textContent = `${Math.max(0, restantes)}s`;
                const porcentaje = Math.max(0, (restantes / segundosTotales) * 100);
                elBarra.style.width = `${porcentaje}%`;

                if (restantes <= 0) {
                    clearInterval(timerInterval);
                    enviarRespuesta('tiempo_agotado', null);
                }
            }, 1000);
        }

        async function enviarRespuesta(letraElegida, btnElemento) {
            if (enviandoRespuesta || !preguntaActualId) return;
            enviandoRespuesta = true;
            clearInterval(timerInterval);

            const tiempoMs = Math.max(150, Date.now() - inicioPreguntaTimestamp);
            document.querySelectorAll('.btn-opcion').forEach(b => b.disabled = true);
            if (btnElemento) {
                btnElemento.classList.add('border-[#00E5FF]', 'bg-[#00E5FF]/20');
            }

            try {
                const res = await fetch('/api/enviar-respuesta', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        pregunta_id: preguntaActualId,
                        opcion_elegida: letraElegida === 'tiempo_agotado' ? null : letraElegida,
                        tiempo_respuesta_ms: tiempoMs
                    })
                });
                const resultado = await res.json();

                const fb = document.getElementById('feedback-respuesta');
                fb.classList.remove('hidden');
                if (resultado.es_correcta) {
                    fb.className = 'p-3 rounded-xl text-center font-orbitron text-xs font-bold bg-emerald-500/20 border border-emerald-400 text-emerald-300';
                    fb.textContent = `✅ ¡CORRECTO! +${resultado.puntos_sumados} PUNTOS`;
                } else {
                    fb.className = 'p-3 rounded-xl text-center font-orbitron text-xs font-bold bg-rose-500/20 border border-rose-500 text-rose-300';
                    fb.textContent = letraElegida === 'tiempo_agotado'
                        ? '⏰ ¡TIEMPO AGOTADO! AVANZANDO A LA SIGUIENTE...'
                        : '❌ RESPUESTA INCORRECTA • AVANZANDO...';
                }

                setTimeout(() => {
                    preguntaActualId = null;
                    enviandoRespuesta = false;
                    sincronizarJuego();
                }, 900);
            } catch (e) {
                enviandoRespuesta = false;
                sincronizarJuego();
            }
        }

        document.addEventListener('visibilitychange', () => {
            if (document.hidden && enPreguntaActiva) {
                document.getElementById('cortina-anti-ia').classList.remove('hidden');
            }
        });

        function desactivarCortina() {
            document.getElementById('cortina-anti-ia').classList.add('hidden');
        }

        sincronizarJuego();
        setInterval(sincronizarJuego, 2000);
    </script>
</body>
</html>