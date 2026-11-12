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
        <div class="text-6xl mb-4 animate-bounce">⚠️</div>
        <h2 class="cyber-title text-2xl sm:text-3xl text-rose-500 mb-2">PREGUNTA ANULADA POR SALIDA</h2>
        <p id="texto-cortina-anti-ia" class="text-slate-200 text-sm max-w-md mb-6 leading-relaxed">
            ⚠️ Saliste de la pantalla de competencia. Esta pregunta quedó anulada con <b class="text-rose-400">0 puntos</b>. Prepárate para la siguiente.
        </p>
        <button onclick="desactivarCortina()" class="px-6 py-3.5 rounded-xl bg-[#00E5FF] hover:bg-cyan-300 text-[#07101E] font-orbitron font-bold text-xs uppercase tracking-wider shadow-[0_0_20px_rgba(0,229,255,0.4)] transition">
            ➡️ CONTINUAR CON LA SIGUIENTE PREGUNTA
        </button>
    </div>

    <!-- BANNER DE ESTADO DE CONEXIÓN (U5) -->
    <div id="banner-sin-conexion" class="hidden fixed top-0 inset-x-0 z-40 bg-rose-600/95 text-white font-orbitron text-xs font-bold text-center py-2.5 px-4 shadow-lg">
        📡 SIN CONEXIÓN AL SERVIDOR • RECONECTANDO AUTOMÁTICAMENTE...
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
            <button type="button" onclick="cerrarSesionEquipo()" title="Salir del Equipo"
                    class="px-3 py-2 rounded-xl bg-rose-500/15 hover:bg-rose-500/30 border border-rose-500/60 text-xs font-orbitron font-bold text-rose-300 transition flex items-center gap-1.5 shrink-0">
                <span>🚪</span>
                <span class="hidden sm:inline">Salir del Equipo</span>
            </button>
        </div>
    </header>

    <!-- CONTENEDOR CENTRAL DINÁMICO -->
    <main class="w-full max-w-lg mx-auto my-auto py-4">

        <!-- 1. PANTALLA DE REGISTRO O RECUPERACIÓN CON PIN -->
        <section id="vista-registro" class="hidden neon-border rounded-2xl p-5 bg-[#0F2342]/90 space-y-4">
            <div class="grid grid-cols-2 gap-2 bg-[#07101E] p-1 rounded-xl border border-slate-800">
                <button type="button" id="tab-nuevo" onclick="cambiarTab('nuevo')"
                        class="py-3 rounded-lg font-orbitron text-xs sm:text-sm font-bold bg-[#00E5FF] text-[#07101E] transition">
                    🚀 NUEVO EQUIPO
                </button>
                <button type="button" id="tab-pin" onclick="cambiarTab('pin')"
                        class="py-3 rounded-lg font-orbitron text-xs sm:text-sm font-bold text-slate-400 hover:text-white transition">
                    🔑 USAR PIN
                </button>
            </div>

            <div id="alerta-error" class="hidden p-3 rounded-xl bg-rose-500/20 border border-rose-500 text-rose-200 text-xs font-semibold"></div>

            <!-- Formulario Nuevo Equipo -->
            <form id="form-registro" class="space-y-4">
                <div>
                    <label class="font-orbitron text-[11px] text-[#00E5FF] uppercase font-bold">Nombre del Equipo *</label>
                    <input type="text" id="reg-nombre-equipo" name="nombre_equipo" required maxlength="40" placeholder="Ej: CyberTitans"
                           class="w-full mt-1 bg-[#07101E] border border-slate-700 focus:border-[#00E5FF] rounded-xl px-3.5 py-2.5 text-base text-white outline-none">
                    <p id="error-reg-nombre_equipo" class="hidden mt-1 text-xs font-semibold text-rose-400"></p>
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
                    <p id="error-reg-integrantes" class="hidden mt-1.5 text-xs font-semibold text-rose-400"></p>
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
                    <input type="text" id="rec-nombre-equipo" name="nombre_equipo" required placeholder="Ej: CyberTitans"
                           class="w-full mt-1 bg-[#07101E] border border-slate-700 focus:border-[#00E5FF] rounded-xl px-3.5 py-2.5 text-base text-white outline-none">
                    <p id="error-rec-nombre_equipo" class="hidden mt-1 text-xs font-semibold text-rose-400"></p>
                </div>
                <div>
                    <label class="font-orbitron text-[11px] text-amber-400 uppercase font-bold">🔑 PIN de Recuperación (4 dígitos)</label>
                    <input type="text" id="rec-pin" name="pin_recuperacion" required maxlength="4" placeholder="Ej: 4829"
                           class="w-full mt-1 bg-[#07101E] border border-amber-400/60 rounded-xl px-3.5 py-2.5 font-orbitron text-lg tracking-widest text-amber-300 text-center outline-none">
                    <p id="error-rec-pin_recuperacion" class="hidden mt-1 text-xs font-semibold text-rose-400"></p>
                </div>
                <button type="submit" id="btn-recuperar"
                        class="w-full py-3.5 rounded-xl bg-amber-400 text-[#07101E] font-orbitron font-bold text-xs uppercase tracking-wider disabled:opacity-50">
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
                        class="w-full sm:w-auto px-5 py-3.5 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 text-rose-200 font-orbitron text-xs font-bold border border-rose-500/60 transition">
                    🚪 Salir del Equipo
                </button>
                <a href="/" class="w-full sm:w-auto px-5 py-3.5 rounded-xl bg-[#07101E] hover:border-[#00E5FF] text-[#00E5FF] font-orbitron text-xs border border-slate-800 transition">
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

            <div id="banner-racha-movil" class="hidden px-3 py-1.5 rounded-xl bg-orange-500/20 border border-orange-400 text-orange-300 font-orbitron text-xs font-bold text-center shadow-[0_0_15px_rgba(251,146,60,0.3)] animate-pulse">
                🔥 ¡ESTÁS EN RACHA x3! ¡No la pierdas!
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
                        class="w-full sm:w-auto px-5 py-3.5 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 text-rose-200 font-orbitron text-xs font-bold border border-rose-500/60 transition">
                    🚪 Salir del Equipo
                </button>
                <a href="/" class="w-full sm:w-auto px-5 py-3.5 rounded-xl bg-[#00E5FF]/20 border border-[#00E5FF] text-[#00E5FF] font-orbitron text-xs font-bold transition">
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
                ? 'py-3 rounded-lg font-orbitron text-xs sm:text-sm font-bold bg-[#00E5FF] text-[#07101E] transition'
                : 'py-3 rounded-lg font-orbitron text-xs sm:text-sm font-bold text-slate-400 hover:text-white transition';
            document.getElementById('tab-pin').className = !esNuevo
                ? 'py-3 rounded-lg font-orbitron text-xs sm:text-sm font-bold bg-amber-400 text-[#07101E] transition'
                : 'py-3 rounded-lg font-orbitron text-xs sm:text-sm font-bold text-slate-400 hover:text-white transition';
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

        function limpiarErroresCampos() {
            document.querySelectorAll('#form-registro input, #form-recuperar input').forEach(inp => {
                inp.classList.remove('border-rose-500');
            });
            ['error-reg-nombre_equipo', 'error-reg-integrantes', 'error-rec-nombre_equipo', 'error-rec-pin_recuperacion'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.textContent = '';
                    el.classList.add('hidden');
                }
            });
        }

        function mostrarErrorCampo(idTexto, selectorInput, mensaje) {
            const el = document.getElementById(idTexto);
            if (el) {
                el.textContent = mensaje;
                el.classList.remove('hidden');
            }
            if (selectorInput) {
                document.querySelectorAll(selectorInput).forEach(inp => inp.classList.add('border-rose-500'));
            }
        }

        document.getElementById('form-registro').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-registrar');
            if (btn.disabled) return;
            btn.disabled = true;

            const alerta = document.getElementById('alerta-error');
            alerta.classList.add('hidden');
            limpiarErroresCampos();

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
                    let primerError = data.error || data.message || 'Verifica los datos ingresados.';
                    if (data.errors) {
                        const keys = Object.keys(data.errors);
                        if (keys.length > 0) primerError = data.errors[keys[0]][0];
                        if (data.errors.nombre_equipo) {
                            mostrarErrorCampo('error-reg-nombre_equipo', '#reg-nombre-equipo', data.errors.nombre_equipo[0]);
                        }
                        const errIntKey = keys.find(k => k === 'integrantes' || k.startsWith('integrantes.'));
                        if (errIntKey) {
                            mostrarErrorCampo('error-reg-integrantes', '#lista-integrantes input', data.errors[errIntKey][0]);
                        }
                    } else if (primerError.toLowerCase().includes('equipo')) {
                        mostrarErrorCampo('error-reg-nombre_equipo', '#reg-nombre-equipo', primerError);
                    } else if (primerError.toLowerCase().includes('integrante') || primerError.toLowerCase().includes('participante')) {
                        mostrarErrorCampo('error-reg-integrantes', '#lista-integrantes input', primerError);
                    }
                    alerta.textContent = primerError;
                    alerta.classList.remove('hidden');
                    return;
                }
                sincronizarJuego();
            } catch (err) {
                alerta.textContent = 'Error de conexión. Intenta nuevamente.';
                alerta.classList.remove('hidden');
            } finally {
                btn.disabled = false;
            }
        });

        document.getElementById('form-recuperar').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-recuperar');
            if (btn && btn.disabled) return;
            if (btn) btn.disabled = true;

            const alerta = document.getElementById('alerta-error');
            alerta.classList.add('hidden');
            limpiarErroresCampos();

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
                    const msg = data.errors ? Object.values(data.errors)[0][0] : (data.error || 'No se pudo recuperar el equipo.');
                    if (data.errors && data.errors.nombre_equipo) {
                        mostrarErrorCampo('error-rec-nombre_equipo', '#rec-nombre-equipo', data.errors.nombre_equipo[0]);
                    }
                    if (data.errors && data.errors.pin_recuperacion) {
                        mostrarErrorCampo('error-rec-pin_recuperacion', '#rec-pin', data.errors.pin_recuperacion[0]);
                    } else {
                        mostrarErrorCampo('error-rec-pin_recuperacion', '#rec-nombre-equipo, #rec-pin', msg);
                    }
                    alerta.textContent = msg;
                    alerta.classList.remove('hidden');
                    return;
                }
                sincronizarJuego();
            } catch (err) {
                alerta.textContent = 'Error al conectar con el servidor.';
                alerta.classList.remove('hidden');
            } finally {
                if (btn) btn.disabled = false;
            }
        });

        let fallosConexionMovil = 0;
        let syncTimerMovil = null;
        let sincronizandoMovil = false;
        let descargandoMovil = false;
        let esRecargaPagina = false;

        window.addEventListener('beforeunload', () => {
            esRecargaPagina = true;
            descargandoMovil = true;
        });

        function actualizarEstadoConexionMovil(conectado) {
            if (descargandoMovil) return;
            const banner = document.getElementById('banner-sin-conexion');
            if (banner) banner.classList.toggle('hidden', conectado);
            if (!enviandoRespuesta) {
                document.querySelectorAll('.btn-opcion').forEach(b => b.disabled = !conectado);
            }
        }

        window.addEventListener('offline', () => actualizarEstadoConexionMovil(false));
        window.addEventListener('online', () => {
            fallosConexionMovil = 0;
            actualizarEstadoConexionMovil(true);
            sincronizarJuego();
        });

        async function sincronizarJuego() {
            if (enviandoRespuesta || sincronizandoMovil || descargandoMovil) return;
            sincronizandoMovil = true;
            if (syncTimerMovil) clearTimeout(syncTimerMovil);

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 8000);

            try {
                const res = await fetch('/api/estado-juego', {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                    signal: controller.signal
                });
                clearTimeout(timeoutId);
                if (res.status === 419) { location.reload(); return; }
                // 429 / 5xx: conservar la vista actual y reintentar en el siguiente ciclo
                if (!res.ok) {
                    fallosConexionMovil++;
                    if (fallosConexionMovil >= 5) actualizarEstadoConexionMovil(false);
                    return;
                }
                fallosConexionMovil = 0;
                actualizarEstadoConexionMovil(true);
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

                const bannerRacha = document.getElementById('banner-racha-movil');
                const rachaActual = Number(data.equipo.racha_actual) || 0;
                if (bannerRacha) {
                    if (rachaActual >= 3) {
                        bannerRacha.textContent = `🔥 ¡ESTÁS EN RACHA x${rachaActual}! ¡No la pierdas!`;
                        bannerRacha.classList.remove('hidden');
                    } else {
                        bannerRacha.classList.add('hidden');
                    }
                }

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
                clearTimeout(timeoutId);
                if (descargandoMovil) return;
                fallosConexionMovil++;
                if (fallosConexionMovil >= 5 || !navigator.onLine) {
                    actualizarEstadoConexionMovil(false);
                }
                console.error('Error sincronizando estado:', e);
            } finally {
                sincronizandoMovil = false;
                if (!descargandoMovil) {
                    const delay = fallosConexionMovil > 0 ? Math.min(8000, 2000 * (fallosConexionMovil + 1)) : 2000;
                    syncTimerMovil = setTimeout(sincronizarJuego, delay);
                }
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

            // Se construye con textContent: opciones como "<div>" se muestran literales y no se interpretan como HTML
            const contenedor = document.getElementById('contenedor-opciones');
            contenedor.replaceChildren(...opciones.map(op => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn-opcion w-full text-left p-3.5 rounded-xl bg-[#07101E] hover:bg-[#00E5FF]/15 border border-slate-700 hover:border-[#00E5FF] transition flex items-center gap-3';
                btn.addEventListener('click', () => enviarRespuesta(op.letra, btn));

                const letra = document.createElement('span');
                letra.className = 'font-orbitron text-xs font-bold w-7 h-7 rounded-lg bg-[#0F2342] border border-[#00E5FF]/50 text-[#00E5FF] flex items-center justify-center uppercase shrink-0';
                letra.textContent = op.letra;

                const texto = document.createElement('span');
                texto.className = 'text-sm sm:text-base font-semibold text-white';
                texto.textContent = op.texto;

                btn.append(letra, texto);
                return btn;
            }));

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

            document.querySelectorAll('.btn-opcion').forEach(b => b.disabled = true);
            if (btnElemento) {
                btnElemento.classList.add('border-[#00E5FF]', 'bg-[#00E5FF]/20');
            }

            try {
                const res = await fetch('/api/enviar-respuesta', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    // El tiempo lo mide el servidor desde la entrega de la pregunta
                    body: JSON.stringify({
                        pregunta_id: preguntaActualId,
                        opcion_elegida: letraElegida === 'tiempo_agotado' ? null : letraElegida
                    })
                });

                const fb = document.getElementById('feedback-respuesta');
                fb.classList.remove('hidden');

                // Sesión/CSRF vencidos: recargar (la sesión del equipo se recupera con el PIN si hiciera falta)
                if (res.status === 419) {
                    mostrarAvisoNeutral(fb, '🔄 SESIÓN EXPIRADA • RECARGANDO...');
                    setTimeout(() => location.reload(), 1200);
                    return;
                }

                // 409 (pregunta no vigente / ronda inactiva), 429 o 5xx: NUNCA mostrar "incorrecta"
                if (!res.ok) {
                    mostrarAvisoNeutral(fb, res.status === 429
                        ? '⏳ DEMASIADAS SOLICITUDES • REINTENTANDO...'
                        : '⚠️ SINCRONIZANDO CON EL SERVIDOR...');
                    setTimeout(() => {
                        preguntaActualId = null; // fuerza re-render de la pregunta vigente
                        enviandoRespuesta = false;
                        sincronizarJuego();
                    }, 1500);
                    return;
                }

                const resultado = await res.json();
                if (resultado.duplicada) {
                    mostrarAvisoNeutral(fb, '✔️ RESPUESTA YA REGISTRADA • AVANZANDO...');
                } else if (resultado.es_correcta) {
                    fb.className = 'p-3 rounded-xl text-center font-orbitron text-xs font-bold bg-emerald-500/20 border border-emerald-400 text-emerald-300';
                    const rachaAct = Number(resultado.racha_actual) || 0;
                    fb.textContent = rachaAct >= 3
                        ? `✅ ¡CORRECTO! +${resultado.puntos_sumados} PUNTOS • 🔥 RACHA x${rachaAct}`
                        : `✅ ¡CORRECTO! +${resultado.puntos_sumados} PUNTOS`;
                } else {
                    fb.className = 'p-3 rounded-xl text-center font-orbitron text-xs font-bold bg-rose-500/20 border border-rose-500 text-rose-300';
                    const baseMsg = (letraElegida === 'tiempo_agotado' || resultado.fuera_de_tiempo)
                        ? '⏰ ¡TIEMPO AGOTADO! AVANZANDO A LA SIGUIENTE...'
                        : '❌ RESPUESTA INCORRECTA • AVANZANDO...';
                    fb.textContent = resultado.racha_perdida
                        ? `${baseMsg} • 💔 Racha perdida (Máxima: ${Number(resultado.racha_maxima) || 0})`
                        : baseMsg;
                }

                setTimeout(() => {
                    preguntaActualId = null;
                    enviandoRespuesta = false;
                    sincronizarJuego();
                }, 900);
            } catch (e) {
                // Error de red: no se sabe si llegó; se resincroniza (si llegó, el servidor responde "ya registrada")
                const fb = document.getElementById('feedback-respuesta');
                fb.classList.remove('hidden');
                mostrarAvisoNeutral(fb, '📡 SIN CONEXIÓN • REINTENTANDO...');
                setTimeout(() => {
                    preguntaActualId = null;
                    enviandoRespuesta = false;
                    sincronizarJuego();
                }, 2000);
            }
        }

        function mostrarAvisoNeutral(fb, texto) {
            fb.className = 'p-3 rounded-xl text-center font-orbitron text-xs font-bold bg-amber-500/20 border border-amber-400 text-amber-200';
            fb.textContent = texto;
        }

        function reportarSalidaPantalla() {
            if (!preguntaActualId || enviandoRespuesta) return;
            const idAnulada = preguntaActualId;
            enviandoRespuesta = true;
            clearInterval(timerInterval);
            document.querySelectorAll('.btn-opcion').forEach(b => b.disabled = true);

            const textoCortina = document.getElementById('texto-cortina-anti-ia');
            if (textoCortina) {
                textoCortina.textContent = '⚠️ Saliste de la pantalla de competencia. Esta pregunta quedó anulada con 0 puntos. Prepárate para la siguiente.';
            }
            document.getElementById('cortina-anti-ia').classList.remove('hidden');

            try {
                fetch('/api/reportar-salida-pantalla', {
                    method: 'POST',
                    keepalive: true,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ pregunta_id: idAnulada })
                })
                .then(res => res.ok ? res.json() : null)
                .then(data => {
                    if (data && data.racha_perdida && textoCortina) {
                        textoCortina.textContent += ` 💔 Racha perdida (Máxima: ${Number(data.racha_maxima) || 0}).`;
                    }
                })
                .catch(() => {});
            } catch (e) {}
        }

        document.addEventListener('visibilitychange', () => {
            if (document.hidden && !esRecargaPagina && enPreguntaActiva && preguntaActualId && !enviandoRespuesta) {
                reportarSalidaPantalla();
            }
        });

        function desactivarCortina() {
            document.getElementById('cortina-anti-ia').classList.add('hidden');
            preguntaActualId = null;
            enviandoRespuesta = false;
            sincronizarJuego();
        }

        sincronizarJuego();
    </script>
</body>
</html>