<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Seven Tech Trivia - Jugar</title>
    @include('partials.cyberpunk-head')
    <style>
        /* CAPA 1 ANTI-TRAMPAS: Prohibido seleccionar, copiar o arrastrar texto */
        .anti-cheat-zone {
            -webkit-user-select: none !important;
            -moz-user-select: none !important;
            -ms-user-select: none !important;
            user-select: none !important;
            -webkit-touch-callout: none !important;
        }

        /* Malla óptica y marca de agua dinámica contra fotos/OCR de IA */
        .ocr-shield {
            position: relative;
            overflow: hidden;
            background-image: repeating-linear-gradient(
                -45deg,
                rgba(0, 229, 255, 0.03) 0px,
                rgba(0, 229, 255, 0.03) 2px,
                transparent 2px,
                transparent 8px
            );
        }

        .watermark-bg {
            pointer-events: none;
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: rotate(-12deg);
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            font-size: 1.6rem;
            color: rgba(0, 229, 255, 0.07);
            white-space: nowrap;
            z-index: 1;
        }
    </style>
</head>
<body class="circuit-bg min-h-screen flex flex-col justify-between p-4 anti-cheat-zone" oncontextmenu="return false;" oncopy="return false;" oncut="return false;" ondragstart="return false;">

    <!-- CORTINA DE BLOQUEO INSTANTÁNEO (ANTI-CAMBIO DE APP / ANTI-CAPTURA) -->
    <div id="escudo-antitrampa" class="hidden fixed inset-0 z-50 bg-[#07101E]/98 backdrop-blur-xl flex flex-col items-center justify-center p-6 text-center">
        <div class="neon-card p-8 rounded-2xl max-w-md border-rose-500">
            <div class="text-5xl mb-3">🛡️</div>
            <h2 class="text-2xl font-bold text-rose-400 uppercase mb-2">Escudo Anti-IA Activado</h2>
            <p class="text-slate-300 text-sm mb-5">Se detectó salida de la pantalla, intento de captura o cambio de aplicación. Por seguridad, la pregunta se oculta mientras estás fuera y el reloj sigue corriendo.</p>
            <button onclick="desbloquearPantalla()" class="w-full py-3 rounded-xl bg-[#00E5FF] text-[#07101E] font-bold uppercase text-sm">
                Volver a la Pregunta Ahora
            </button>
        </div>
    </div>

    <!-- CABECERA COMPACTA -->
    <header class="max-w-xl w-full mx-auto flex items-center justify-between py-3 px-4 neon-card rounded-2xl mb-4">
        <div class="flex items-center gap-3">
            <a href="/" title="Volver al Portal Principal">
                <img src="/favicon.svg" alt="Seven Tech" class="w-11 h-11 rounded-xl hover:scale-105 transition">
            </a>
            <div>
                <h1 class="cyber-title text-xl md:text-2xl">SEVEN TECH</h1>
                <p class="font-orbitron text-[10px] text-slate-300">{{ $config->nombre_evento }}</p>
            </div>
        </div>
        <div id="info-equipo-header" class="{{ $equipo ? '' : 'hidden' }} text-right">
            <p id="header-nombre-equipo" class="text-sm font-bold text-white">{{ $equipo->nombre_equipo ?? '' }}</p>
            <div id="info-equipo-header" class="hidden flex items-center gap-2">
            <div class="text-center bg-[#07101E] px-2.5 py-1 rounded-lg border border-slate-700">
                <span class="font-orbitron block text-[8px] text-slate-400 uppercase">Torneo</span>
                <span id="reloj-global-movil" class="font-orbitron text-xs font-bold text-amber-400">--:--</span>
            </div>
            <div class="text-right bg-[#07101E] px-3 py-1 rounded-lg border border-[#00E5FF]/40">
                <p id="header-nombre-equipo" class="text-xs font-bold text-white leading-tight"></p>
                <p id="header-puntaje" class="font-orbitron text-xs text-[#00E5FF] font-bold">0 PTS</p>
            </div>
        </div>
        </div>
    </header>

    <!-- CONTENEDOR CENTRAL -->
    <main class="max-w-xl w-full mx-auto my-auto">

        <!-- 1. PANTALLA DE REGISTRO O RECUPERACIÓN CON PIN -->
        <div id="vista-registro" class="{{ $equipo ? 'hidden' : '' }} neon-card rounded-2xl p-6">
            
            <!-- Pestañas: Nuevo Registro vs Recuperar con PIN -->
            <div class="grid grid-cols-2 gap-2 mb-5 bg-[#07101E] p-1.5 rounded-xl border border-slate-800">
                <button type="button" id="tab-btn-nuevo" onclick="cambiarTab('nuevo')" class="py-2 rounded-lg font-orbitron text-[11px] font-bold bg-[#00E5FF] text-[#07101E] transition">
                    🚀 NUEVO EQUIPO
                </button>
                <button type="button" id="tab-btn-recuperar" onclick="cambiarTab('recuperar')" class="py-2 rounded-lg font-orbitron text-[11px] font-bold text-slate-300 hover:text-white transition">
                    🔑 USAR PIN
                </button>
            </div>

            <div id="alerta-error" class="hidden mb-4 p-3 rounded-xl bg-rose-500/20 border border-rose-500 text-rose-200 text-sm font-semibold"></div>

            <!-- FORMULARIO A: REGISTRAR NUEVO EQUIPO -->
            <form id="form-registro" class="space-y-4">
                <div>
                    <label class="block font-orbitron text-[11px] font-bold uppercase text-[#00E5FF] mb-1">Nombre del Equipo *</label>
                    <input type="text" id="nombre_equipo" required maxlength="40" placeholder="Ej: CyberTitans UEB"
                           class="w-full bg-[#07101E] border border-slate-700 focus:border-[#00E5FF] rounded-xl px-4 py-3 text-base text-white outline-none">
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="font-orbitron text-[11px] font-bold uppercase text-[#00E5FF]">
                            Integrantes (Mín 2 / Máx {{ $config->max_integrantes_equipo }}) *
                        </label>
                        <button type="button" id="btn-agregar-integrante" class="font-orbitron text-[10px] bg-cyan-500/20 hover:bg-cyan-500/30 text-[#00E5FF] border border-[#00E5FF]/50 px-3 py-1.5 rounded-lg font-bold">
                            + Añadir integrante
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-400 mb-2">⚠️ Ingresa el <b>Nombre y Apellido real</b> de cada participante (son privados y solo visibles para el jurado/administrador).</p>
                    <div id="lista-integrantes" class="space-y-2">
                        <input type="text" name="integrantes[]" required placeholder="Nombre y Apellido del Capitán (Ej: Valentín Valle) *"
                               class="w-full bg-[#07101E] border border-slate-700 focus:border-[#00E5FF] rounded-xl px-3.5 py-2.5 text-base text-white outline-none">
                        <input type="text" name="integrantes[]" required placeholder="Nombre y Apellido del Integrante 2 *"
                               class="w-full bg-[#07101E] border border-slate-700 focus:border-[#00E5FF] rounded-xl px-3.5 py-2.5 text-base text-white outline-none">
                    </div>
                </div>

                <button type="submit" class="w-full py-3.5 rounded-xl bg-[#00E5FF] hover:bg-cyan-400 text-[#07101E] font-bold text-base uppercase tracking-wider transition">
                    🚀 Registrar Equipo y Generar PIN
                </button>
            </form>

            <!-- FORMULARIO B: RECUPERAR EQUIPO CON PIN DE 4 DÍGITOS -->
            <form id="form-recuperar" class="hidden space-y-4">
                <p class="text-sm text-slate-300">Si se te cerró el navegador o cambiaste de celular, ingresa el nombre exacto de tu equipo y tu PIN de 4 dígitos para continuar donde estabas:</p>
                <div>
                    <label class="block font-orbitron text-[11px] font-bold uppercase text-[#00E5FF] mb-1">Nombre Registrado del Equipo *</label>
                    <input type="text" id="rec_nombre_equipo" required placeholder="Ej: DataClub Team"
                           class="w-full bg-[#07101E] border border-slate-700 focus:border-[#00E5FF] rounded-xl px-4 py-3 text-base text-white outline-none">
                </div>
                <div>
                    <label class="block font-orbitron text-[11px] font-bold uppercase text-amber-400 mb-1">PIN de Recuperación (4 dígitos) *</label>
                    <input type="text" id="rec_pin" required maxlength="4" placeholder="Ej: 4829"
                           class="w-full bg-[#07101E] border border-amber-400/60 focus:border-amber-400 rounded-xl px-4 py-3 text-xl font-orbitron tracking-widest text-amber-300 outline-none">
                </div>
                <button type="submit" class="w-full py-3.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-[#07101E] font-bold text-base uppercase tracking-wider transition">
                    🔑 Recuperar Sesión del Equipo
                </button>
            </form>
        </div>

        <!-- 2. SALA DE ESPERA -->
        <div id="vista-espera" class="hidden neon-card rounded-2xl p-8 text-center">
            <div class="text-5xl mb-4 animate-bounce">🏁</div>
            <h2 id="titulo-espera" class="text-2xl font-bold text-white mb-2">¡Equipo en Línea de Salida!</h2>
            <p id="mensaje-espera" class="text-base text-slate-300 mb-5">Permanece en esta pantalla. La carrera comenzará en cuanto el Administrador dé la señal.</p>
            
            <div class="grid grid-cols-2 gap-3 max-w-sm mx-auto">
                <div class="bg-[#07101E] border border-slate-800 rounded-xl p-3">
                    <p class="font-orbitron text-[9px] text-slate-400 uppercase">Puntaje</p>
                    <p id="espera-puntaje" class="font-orbitron text-2xl font-bold text-[#00E5FF] mt-1">0 PTS</p>
                </div>
                <div class="bg-[#07101E] border border-amber-500/40 rounded-xl p-3">
                    <p class="font-orbitron text-[9px] text-amber-300 uppercase">Tu PIN de Respaldo</p>
                    <p id="espera-pin" class="font-orbitron text-2xl font-bold text-amber-400 mt-1">{{ $equipo->pin_recuperacion ?? '----' }}</p>
                </div>
            </div>
        </div>

        <!-- 3. PANTALLA DE PREGUNTA EN VIVO (BLINDADA ANTI-IA) -->
        <div id="vista-pregunta" class="hidden neon-card rounded-2xl p-6">
            <div class="flex justify-between items-center mb-3">
                <span id="badge-nivel" class="px-3 py-1 rounded-full text-[11px] font-bold uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-400">
                    Etapa 1
                </span>
                <span id="progreso-fase" class="text-xs font-bold text-slate-300">Pregunta 1 / 5</span>
            </div>

            <div class="w-full bg-[#07101E] h-3 rounded-full overflow-hidden mb-2 border border-slate-800">
                <div id="barra-tiempo" class="bg-[#00E5FF] h-full transition-all duration-200" style="width: 100%;"></div>
            </div>
            <p class="text-right text-xs font-bold text-[#00E5FF] mb-4">⏱️ <span id="segundos-restantes">25</span>s</p>

            <!-- Caja de Pregunta con Malla Anti-OCR y Marca de Agua del Equipo -->
            <div class="bg-[#07101E] border border-slate-800 rounded-xl p-4 mb-5 ocr-shield">
                <div id="watermark-equipo" class="watermark-bg">SEVEN TECH</div>
                <h3 id="texto-pregunta" class="relative z-10 text-lg md:text-xl font-bold text-white leading-snug">Cargando pregunta...</h3>
            </div>

            <div class="grid grid-cols-1 gap-3" id="contenedor-opciones">
                <button onclick="seleccionarOpcion('a')" class="opcion-btn text-left p-3.5 rounded-xl bg-[#07101E] hover:bg-[#00E5FF]/20 border border-slate-700 hover:border-[#00E5FF] transition flex items-center gap-3">
                    <span class="font-orbitron w-8 h-8 rounded-lg bg-[#0F2342] border border-[#00E5FF] flex items-center justify-center font-bold text-[#00E5FF]">A</span>
                    <span id="op-a" class="text-base font-medium text-white flex-1"></span>
                </button>
                <button onclick="seleccionarOpcion('b')" class="opcion-btn text-left p-3.5 rounded-xl bg-[#07101E] hover:bg-[#00E5FF]/20 border border-slate-700 hover:border-[#00E5FF] transition flex items-center gap-3">
                    <span class="font-orbitron w-8 h-8 rounded-lg bg-[#0F2342] border border-[#00E5FF] flex items-center justify-center font-bold text-[#00E5FF]">B</span>
                    <span id="op-b" class="text-base font-medium text-white flex-1"></span>
                </button>
                <button onclick="seleccionarOpcion('c')" class="opcion-btn text-left p-3.5 rounded-xl bg-[#07101E] hover:bg-[#00E5FF]/20 border border-slate-700 hover:border-[#00E5FF] transition flex items-center gap-3">
                    <span class="font-orbitron w-8 h-8 rounded-lg bg-[#0F2342] border border-[#00E5FF] flex items-center justify-center font-bold text-[#00E5FF]">C</span>
                    <span id="op-c" class="text-base font-medium text-white flex-1"></span>
                </button>
                <button onclick="seleccionarOpcion('d')" class="opcion-btn text-left p-3.5 rounded-xl bg-[#07101E] hover:bg-[#00E5FF]/20 border border-slate-700 hover:border-[#00E5FF] transition flex items-center gap-3">
                    <span class="font-orbitron w-8 h-8 rounded-lg bg-[#0F2342] border border-[#00E5FF] flex items-center justify-center font-bold text-[#00E5FF]">D</span>
                    <span id="op-d" class="text-base font-medium text-white flex-1"></span>
                </button>
            </div>
        </div>

    </main>

    <footer class="text-center text-[11px] text-slate-500 py-3">
        Seven Tech Community • Protección Anti-IA Activa
    </footer>

    <script>
        const maxIntegrantes = {{ $config->max_integrantes_equipo }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        let preguntaActualId = null;
        let tiempoInicioPregunta = null;
        let timerInterval = null;
        let enviandoRespuesta = false;

        // Alternar pestañas entre Registro Nuevo y Recuperar con PIN
        function cambiarTab(modo) {
            const formReg = document.getElementById('form-registro');
            const formRec = document.getElementById('form-recuperar');
            const btnNuevo = document.getElementById('tab-btn-nuevo');
            const btnRec = document.getElementById('tab-btn-recuperar');
            document.getElementById('alerta-error').classList.add('hidden');

            if (modo === 'nuevo') {
                formReg.classList.remove('hidden');
                formRec.classList.add('hidden');
                btnNuevo.className = 'py-2 rounded-lg font-orbitron text-[11px] font-bold bg-[#00E5FF] text-[#07101E] transition';
                btnRec.className = 'py-2 rounded-lg font-orbitron text-[11px] font-bold text-slate-300 hover:text-white transition';
            } else {
                formReg.classList.add('hidden');
                formRec.classList.remove('hidden');
                btnRec.className = 'py-2 rounded-lg font-orbitron text-[11px] font-bold bg-amber-400 text-[#07101E] transition';
                btnNuevo.className = 'py-2 rounded-lg font-orbitron text-[11px] font-bold text-slate-300 hover:text-white transition';
            }
        }

        // Añadir integrantes
        const btnAgregar = document.getElementById('btn-agregar-integrante');
        const listaIntegrantes = document.getElementById('lista-integrantes');
        if (btnAgregar) {
            btnAgregar.addEventListener('click', () => {
                const total = listaIntegrantes.querySelectorAll('input').length;
                if (total >= maxIntegrantes) {
                    alert('El límite máximo es de ' + maxIntegrantes + ' integrantes por equipo.');
                    return;
                }
                const div = document.createElement('div');
                div.className = 'flex gap-2';
                div.innerHTML = `
                    <input type="text" name="integrantes[]" required placeholder="Nombre y Apellido del Integrante ${total + 1} *"
                           class="w-full bg-[#07101E] border border-slate-700 focus:border-[#00E5FF] rounded-xl px-3.5 py-2.5 text-base text-white outline-none">
                    <button type="button" onclick="this.parentElement.remove()" class="px-3 rounded-xl bg-rose-500/20 text-rose-400 border border-rose-500/40 font-bold">✕</button>
                `;
                listaIntegrantes.appendChild(div);
            });
        }

        // Enviar Registro Nuevo
        const formRegistro = document.getElementById('form-registro');
        if (formRegistro) {
            formRegistro.addEventListener('submit', async (e) => {
                e.preventDefault();
                const alerta = document.getElementById('alerta-error');
                alerta.classList.add('hidden');

                const integrantes = Array.from(document.querySelectorAll('input[name="integrantes[]"]')).map(i => i.value);
                const nombre_equipo = document.getElementById('nombre_equipo').value;

                const res = await fetch('/api/registrar-equipo', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ nombre_equipo, integrantes })
                });

                const data = await res.json();
                if (!res.ok) {
                    const primerError = data.errors ? Object.values(data.errors)[0][0] : (data.error || data.message);
                    alerta.textContent = primerError || 'Error al registrar el equipo.';
                    alerta.classList.remove('hidden');
                    return;
                }

                activarSesionUI(data.equipo);
            });
        }

        // Enviar Recuperación por PIN
        const formRecuperar = document.getElementById('form-recuperar');
        if (formRecuperar) {
            formRecuperar.addEventListener('submit', async (e) => {
                e.preventDefault();
                const alerta = document.getElementById('alerta-error');
                alerta.classList.add('hidden');

                const nombre_equipo = document.getElementById('rec_nombre_equipo').value;
                const pin_recuperacion = document.getElementById('rec_pin').value;

                const res = await fetch('/api/recuperar-equipo', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ nombre_equipo, pin_recuperacion })
                });

                const data = await res.json();
                if (!res.ok) {
                    alerta.textContent = data.message || data.error || 'No se pudo recuperar el equipo.';
                    alerta.classList.remove('hidden');
                    return;
                }

                activarSesionUI(data.equipo);
            });
        }

        function activarSesionUI(equipo) {
            document.getElementById('vista-registro').classList.add('hidden');
            document.getElementById('info-equipo-header').classList.remove('hidden');
            document.getElementById('header-nombre-equipo').textContent = equipo.nombre_equipo;
            document.getElementById('header-pin-equipo').textContent = equipo.pin_recuperacion;
            document.getElementById('espera-pin').textContent = equipo.pin_recuperacion;
            sincronizarEstado();
        }

        // Ofuscar texto con caracteres invisibles Zero-Width para confundir OCR/Copiado IA
        function protegerTextoAntiIA(texto) {
            return texto.split(' ').join(' \u200B');
        }

        // ESCUDO ANTI-CAMBIO DE VENTANA Y ANTI-CAPTURA (Solo activo cuando hay pregunta en curso)
        function activarCortinaSeguridad() {
            if (preguntaActualId !== null) {
                document.getElementById('escudo-antitrampa').classList.remove('hidden');
            }
        }

        function desbloquearPantalla() {
            document.getElementById('escudo-antitrampa').classList.add('hidden');
        }

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) activarCortinaSeguridad();
        });

        window.addEventListener('blur', () => {
            activarCortinaSeguridad();
        });

        window.addEventListener('keyup', (e) => {
            if (e.key === 'PrintScreen' || (e.ctrlKey && ['p', 's', 'u', 'c'].includes(e.key.toLowerCase())) || e.key === 'F12') {
                activarCortinaSeguridad();
                if (navigator.clipboard) navigator.clipboard.writeText('Bloqueado por Seven Tech Anti-Cheat');
            }
        });

        async function sincronizarEstado() {
            if (enviandoRespuesta) return;
            try {
                const res = await fetch('/api/estado-juego');
                const data = await res.json();
                const elRelojMovil = document.getElementById('reloj-global-movil');
                if (elRelojMovil) {
                    if (data.segundos_globales !== null && data.segundos_globales !== undefined) {
                        const m = Math.floor(data.segundos_globales / 60).toString().padStart(2, '0');
                        const s = Math.floor(data.segundos_globales % 60).toString().padStart(2, '0');
                        elRelojMovil.textContent = `${m}:${s}`;
                    } else {
                        elRelojMovil.textContent = (data.estado_juego || '--').toUpperCase();
                    }
                }
                if (!data.autenticado) return;

                document.getElementById('header-puntos-equipo').textContent = data.equipo.puntaje_total + ' PTS';
                document.getElementById('header-pin-equipo').textContent = data.equipo.pin_recuperacion;
                document.getElementById('espera-puntaje').textContent = data.equipo.puntaje_total + ' PTS';
                document.getElementById('espera-pin').textContent = data.equipo.pin_recuperacion;
                document.getElementById('watermark-equipo').textContent = data.equipo.nombre_equipo + ' • SEVEN TECH';

                const vistaEspera = document.getElementById('vista-espera');
                const vistaPregunta = document.getElementById('vista-pregunta');

                if (data.pregunta) {
                    vistaEspera.classList.add('hidden');
                    vistaPregunta.classList.remove('hidden');
                    if (preguntaActualId !== data.pregunta.id) {
                        cargarNuevaPregunta(data.pregunta, data.nivel, data.progreso_fase);
                    }
                } else {
                    vistaPregunta.classList.add('hidden');
                    vistaEspera.classList.remove('hidden');
                    desbloquearPantalla();
                    clearInterval(timerInterval);
                    preguntaActualId = null;

                    const titulo = document.getElementById('titulo-espera');
                    const msg = document.getElementById('mensaje-espera');

                    if (data.estado_juego === 'registro') {
                        titulo.textContent = '¡Equipo Registrado y en Pista!';
                        msg.textContent = 'Anota tu PIN de respaldo de 4 dígitos. La carrera comenzará pronto...';
                    } else if (data.estado_juego === 'pausado') {
                        titulo.textContent = '⏸️ Carrera en Pausa';
                        msg.textContent = 'El Administrador ha pausado momentáneamente la partida.';
                    } else if (data.estado_juego === 'esperando_desempate') {
                        titulo.textContent = '🔥 Ronda de Muerte Súbita en Curso';
                        msg.textContent = 'Los equipos empatados están definiendo al ganador en pantalla gigante.';
                    } else if (data.estado_juego === 'carrera_completada' || data.estado_juego === 'finalizado') {
                        titulo.textContent = '🏆 ¡Has cruzado la Meta!';
                        msg.textContent = 'Completaste todas las etapas. ¡Mira el Podio de Ganadores en la pantalla del auditorio!';
                    }
                }
            } catch (err) {
                console.error(err);
            }
        }

        function cargarNuevaPregunta(pregunta, nivel, progreso) {
            preguntaActualId = pregunta.id;
            tiempoInicioPregunta = Date.now();

            document.getElementById('badge-nivel').textContent = nivel.nombre;
            document.getElementById('progreso-fase').textContent = 'Pregunta ' + progreso;
            document.getElementById('texto-pregunta').textContent = protegerTextoAntiIA(pregunta.enunciado);
            document.getElementById('op-a').textContent = protegerTextoAntiIA(pregunta.opcion_a);
            document.getElementById('op-b').textContent = protegerTextoAntiIA(pregunta.opcion_b);
            document.getElementById('op-c').textContent = protegerTextoAntiIA(pregunta.opcion_c);
            document.getElementById('op-d').textContent = protegerTextoAntiIA(pregunta.opcion_d);

            document.querySelectorAll('.opcion-btn').forEach(b => b.disabled = false);

            clearInterval(timerInterval);
            const limiteSeg = nivel.tiempo_limite_seg || 25;
            let restantes = limiteSeg;
            document.getElementById('segundos-restantes').textContent = restantes;
            document.getElementById('barra-tiempo').style.width = '100%';

            timerInterval = setInterval(() => {
                restantes--;
                document.getElementById('segundos-restantes').textContent = Math.max(0, restantes);
                document.getElementById('barra-tiempo').style.width = Math.max(0, (restantes / limiteSeg) * 100) + '%';

                if (restantes <= 0) {
                    clearInterval(timerInterval);
                    seleccionarOpcion(null);
                }
            }, 1000);
        }

        async function seleccionarOpcion(opcion) {
            if (enviandoRespuesta || !preguntaActualId) return;
            enviandoRespuesta = true;
            clearInterval(timerInterval);

            document.querySelectorAll('.opcion-btn').forEach(b => b.disabled = true);
            const tiempoMs = Math.min(120000, Math.max(150, Date.now() - tiempoInicioPregunta));

            try {
                await fetch('/api/enviar-respuesta', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        pregunta_id: preguntaActualId,
                        opcion_elegida: opcion,
                        tiempo_respuesta_ms: tiempoMs
                    })
                });
            } catch (e) {
                console.error(e);
            }

            preguntaActualId = null;
            enviandoRespuesta = false;
            sincronizarEstado();
        }

        setInterval(sincronizarEstado, 2000);
        sincronizarEstado();
    </script>
</body>
</html>