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
    <header class="w-full max-w-lg mx-auto neon-border rounded-2xl p-3 sm:p-4 flex flex-wrap items-center justify-between gap-2.5 bg-[#0f172a]/95">
        <a href="/" title="Volver al Portal Principal" class="flex items-center gap-2.5 sm:gap-3 hover:opacity-90 transition min-w-0 shrink-0">
            <img src="/favicon.svg" alt="Seven Tech" class="w-10 h-10 rounded-xl border border-emerald-400/70 shadow-[0_0_12px_rgba(16,185,129,0.28)] shrink-0">
            <div class="min-w-0">
                <h1 class="cyber-title text-base sm:text-xl leading-none">SEVEN TECH</h1>
                <p class="font-orbitron text-[10px] text-slate-300 mt-1">Seven Tech Trivia 2026</p>
            </div>
        </a>

        <!-- Información del equipo activo + Reloj Global + Botón Salir -->
        <div id="info-equipo-header" class="hidden flex flex-wrap sm:flex-nowrap items-center justify-end gap-1.5 sm:gap-2 min-w-0 ml-auto">
            <div class="text-center bg-[#0b1322] px-2.5 py-1.5 rounded-xl border border-slate-700/80 shrink-0">
                <span class="font-orbitron block text-[8px] text-slate-400 uppercase tracking-wider">Torneo</span>
                <span id="reloj-global-movil" class="font-orbitron text-xs font-bold text-amber-400 whitespace-nowrap">--:--</span>
            </div>
            <div class="text-right bg-[#0b1322] px-2.5 sm:px-3 py-1.5 rounded-xl border border-emerald-500/45 min-w-0">
                <p id="header-nombre-equipo" class="text-xs font-bold text-white leading-tight truncate max-w-[105px] sm:max-w-[180px]"></p>
                <p id="header-puntaje" class="font-orbitron text-xs text-emerald-300 font-bold whitespace-nowrap">0 PTS</p>
            </div>
            <button type="button" onclick="cerrarSesionEquipo()" title="Salir del Equipo" aria-label="Salir del Equipo"
                    class="min-h-[48px] px-3 py-2 rounded-xl bg-slate-800/70 hover:bg-emerald-500/20 backdrop-blur-md border border-emerald-400/45 text-xs font-orbitron font-bold text-emerald-200 hover:text-white shadow-[0_0_14px_rgba(16,185,129,0.16)] transition flex items-center gap-1.5 shrink-0 whitespace-nowrap">
                <svg class="w-4 h-4 shrink-0 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                </svg>
                <span class="hidden sm:inline">Salir</span>
            </button>
        </div>
    </header>

    <!-- CONTENEDOR CENTRAL DINÁMICO -->
    <main class="w-full max-w-lg mx-auto my-auto py-4">

        <!-- 1. PANTALLA DE REGISTRO O RECUPERACIÓN CON PIN -->
        <section id="vista-registro" class="hidden neon-border rounded-2xl p-4 sm:p-5 bg-[#0f172a]/95 space-y-4">
            <div class="grid grid-cols-2 gap-2 bg-[#0b1322] p-1 rounded-xl border border-slate-800">
                <button type="button" id="tab-nuevo" onclick="cambiarTab('nuevo')"
                        class="min-h-[48px] py-3 rounded-lg font-orbitron text-xs sm:text-sm font-bold bg-emerald-500 text-slate-950 transition">
                    🚀 NUEVO EQUIPO
                </button>
                <button type="button" id="tab-pin" onclick="cambiarTab('pin')"
                        class="min-h-[48px] py-3 rounded-lg font-orbitron text-xs sm:text-sm font-bold text-slate-400 hover:text-white transition">
                    🔑 USAR PIN
                </button>
            </div>

            <!-- Formulario Nuevo Equipo (validación nativa con setCustomValidity/reportValidity) -->
            <form id="form-registro" class="space-y-4">
                <div>
                    <label for="reg-nombre-equipo" class="block font-orbitron text-[11px] text-emerald-300 uppercase font-bold mb-1">Nombre del Equipo *</label>
                    <input type="text" id="reg-nombre-equipo" name="nombre_equipo" required minlength="2" maxlength="40" autocomplete="organization" placeholder="Ej: CyberTitans"
                           class="w-full min-h-[48px] bg-[#0b1322] border border-slate-700 focus:border-emerald-400 rounded-xl px-3.5 py-2.5 text-base text-slate-100 placeholder-slate-500 outline-none transition">
                </div>

                <div>
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                        <label for="reg-integrante-1" class="font-orbitron text-[11px] text-emerald-300 uppercase font-bold">
                            Integrantes (Mín 2 / Máx {{ $config->max_integrantes_equipo ?? 5 }}) *
                        </label>
                        <button type="button" onclick="agregarCampoIntegrante()"
                                class="min-h-[48px] text-xs px-3 py-2 rounded-lg bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-400/50 text-emerald-300 font-bold transition">
                            + Añadir integrante
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-400 mb-2">⚠️ Ingresa el <b>Nombre y Apellido real</b> de cada participante (son privados y solo visibles para el jurado/administrador).</p>

                    <div id="lista-integrantes" class="space-y-2.5">
                        <div>
                            <label for="reg-integrante-1" class="sr-only">Nombre y Apellido del Capitán</label>
                            <input type="text" id="reg-integrante-1" name="integrantes[]" required minlength="3" autocomplete="name" placeholder="Nombre y Apellido del Capitán *"
                                   class="w-full min-h-[48px] bg-[#0b1322] border border-slate-700 focus:border-emerald-400 rounded-xl px-3.5 py-2.5 text-base text-slate-100 placeholder-slate-500 outline-none transition">
                        </div>
                        <div>
                            <label for="reg-integrante-2" class="sr-only">Nombre y Apellido del Integrante 2</label>
                            <input type="text" id="reg-integrante-2" name="integrantes[]" required minlength="3" autocomplete="name" placeholder="Nombre y Apellido del Integrante 2 *"
                                   class="w-full min-h-[48px] bg-[#0b1322] border border-slate-700 focus:border-emerald-400 rounded-xl px-3.5 py-2.5 text-base text-slate-100 placeholder-slate-500 outline-none transition">
                        </div>
                    </div>
                </div>

                <button type="submit" id="btn-registrar"
                        class="w-full min-h-[48px] py-3.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-orbitron font-bold text-xs uppercase tracking-wider shadow-[0_0_20px_rgba(16,185,129,0.35)] transition">
                    🚀 REGISTRAR EQUIPO Y GENERAR PIN
                </button>
            </form>

            <!-- Formulario Recuperar Sesión con PIN -->
            <form id="form-recuperar" class="hidden space-y-4">
                <p class="text-xs text-slate-300">Si se cerró tu navegador o cambiaste de teléfono, ingresa el nombre de tu equipo y tu PIN de 4 dígitos:</p>
                <div>
                    <label for="rec-nombre-equipo" class="block font-orbitron text-[11px] text-emerald-300 uppercase font-bold mb-1">Nombre Exacto del Equipo</label>
                    <input type="text" id="rec-nombre-equipo" name="nombre_equipo" required minlength="2" maxlength="40" autocomplete="organization" placeholder="Ej: CyberTitans"
                           class="w-full min-h-[48px] bg-[#0b1322] border border-slate-700 focus:border-emerald-400 rounded-xl px-3.5 py-2.5 text-base text-slate-100 placeholder-slate-500 outline-none transition">
                </div>
                <div>
                    <label for="rec-pin" class="block font-orbitron text-[11px] text-amber-400 uppercase font-bold mb-1">🔑 PIN de Recuperación (4 dígitos)</label>
                    <input type="text" id="rec-pin" name="pin_recuperacion" required pattern="\d{4}" minlength="4" maxlength="4" placeholder="Ej: 4829" inputmode="numeric" autocomplete="one-time-code"
                           class="w-full min-h-[48px] bg-[#0b1322] border border-amber-400/60 focus:border-amber-300 rounded-xl px-3.5 py-2.5 font-orbitron text-base sm:text-lg tracking-widest text-amber-300 text-center placeholder-slate-500 outline-none transition">
                </div>
                <button type="submit" id="btn-recuperar"
                        class="w-full min-h-[48px] py-3.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-orbitron font-bold text-xs uppercase tracking-wider disabled:opacity-50 transition">
                    🔄 RECUPERAR MI CARRERA
                </button>
            </form>

            <div class="pt-2 border-t border-slate-800 text-center">
                <a href="/" class="text-xs text-slate-400 hover:text-emerald-300 font-orbitron transition">← Volver al Portal Principal</a>
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
                        class="w-full sm:w-auto min-h-[48px] px-5 py-3.5 rounded-xl bg-slate-800/70 hover:bg-emerald-500/20 backdrop-blur-md border border-emerald-400/45 text-emerald-200 hover:text-white font-orbitron text-xs font-bold transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 shrink-0 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                    </svg>
                    <span>Salir del Equipo</span>
                </button>
                <a href="/" class="w-full sm:w-auto min-h-[48px] px-5 py-3.5 rounded-xl bg-[#0b1322] hover:border-emerald-400 text-emerald-300 font-orbitron text-xs border border-slate-700 transition flex items-center justify-center">
                    🏠 Portal Principal
                </a>
            </div>
        </section>

        <!-- 3. PANTALLA DE PREGUNTA ACTIVA -->
        <section id="vista-pregunta" class="hidden neon-border rounded-2xl p-5 bg-[#0f172a]/95 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-2.5">
                <div>
                    <span id="badge-nivel" class="font-orbitron text-[10px] font-bold uppercase px-2.5 py-1 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-400/50">
                        ETAPA 1
                    </span>
                    <span id="progreso-fase" class="font-orbitron text-[10px] text-slate-400 ml-2">1 / 5</span>
                </div>
                <div class="flex items-center gap-1.5 bg-[#0b1322] px-3 py-1 rounded-xl border border-slate-700">
                    <span class="text-xs">⏱️</span>
                    <span id="reloj-pregunta" class="font-orbitron text-base font-bold text-emerald-300">25s</span>
                </div>
            </div>

            <!-- Barra de tiempo visual -->
            <div class="w-full h-2 bg-[#0b1322] rounded-full overflow-hidden border border-slate-800">
                <div id="barra-tiempo" class="h-full bg-emerald-400 transition-all duration-300" style="width: 100%;"></div>
            </div>

            <div id="banner-racha-movil" class="hidden px-3 py-1.5 rounded-xl bg-orange-500/20 border border-orange-400 text-orange-300 font-orbitron text-xs font-bold text-center shadow-[0_0_15px_rgba(251,146,60,0.3)] animate-pulse">
                🔥 ¡ESTÁS EN RACHA x3! ¡No la pierdas!
            </div>

            <h3 id="texto-enunciado" class="text-lg sm:text-xl font-bold text-white leading-snug py-1"></h3>

            <div id="contenedor-opciones" class="space-y-3 pt-1"></div>

            <div id="feedback-respuesta" role="status" aria-live="polite" class="hidden">
                <span id="feedback-respuesta-icono" aria-hidden="true" class="shrink-0"></span>
                <span id="feedback-respuesta-texto"></span>
            </div>
        </section>

        <!-- 4. PANTALLA DE BANDERITA: META CRUZADA / EVENTO FINALIZADO -->
        <section id="vista-final" class="hidden neon-border rounded-2xl p-6 bg-[#0f172a]/95 text-center space-y-4">
            <div id="icono-meta" class="text-6xl animate-bounce">🏁</div>
            <h2 id="titulo-meta" class="cyber-title text-2xl sm:text-3xl">¡CRUZASTE LA META!</h2>
            <p id="subtitulo-meta" class="text-slate-300 text-sm">
                Tu equipo ha completado la carrera de <b>Seven Tech</b>. Mira la pantalla gigante del auditorio para conocer el podio oficial.
            </p>

            <div class="grid grid-cols-2 gap-3 bg-[#0b1322] p-4 rounded-xl border border-emerald-500/45">
                <div>
                    <p class="font-orbitron text-[10px] text-slate-400 uppercase">Puntaje Final</p>
                    <p id="puntaje-final-texto" class="font-orbitron text-2xl sm:text-3xl font-bold text-emerald-400 mt-1">0 PTS</p>
                </div>
                <div class="border-l border-slate-800">
                    <p class="font-orbitron text-[10px] text-slate-400 uppercase">Aciertos</p>
                    <p id="aciertos-final-texto" class="font-orbitron text-2xl sm:text-3xl font-bold text-emerald-300 mt-1">0</p>
                </div>
            </div>

            <div class="p-3 rounded-xl bg-[#0b1322]/70 border border-slate-800 text-xs text-slate-400">
                🔑 PIN de tu equipo: <b id="pin-final-texto" class="font-orbitron text-amber-400">----</b>
            </div>

            <div class="pt-2 flex flex-wrap items-center justify-center gap-3">
                <button type="button" onclick="cerrarSesionEquipo()"
                        class="w-full sm:w-auto min-h-[48px] px-5 py-3.5 rounded-xl bg-slate-800/70 hover:bg-emerald-500/20 backdrop-blur-md border border-emerald-400/45 text-emerald-200 hover:text-white font-orbitron text-xs font-bold transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 shrink-0 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                    </svg>
                    <span>Salir del Equipo</span>
                </button>
                <a href="/" class="w-full sm:w-auto min-h-[48px] px-5 py-3.5 rounded-xl bg-emerald-500/20 border border-emerald-400 text-emerald-300 font-orbitron text-xs font-bold transition flex items-center justify-center">
                    🏠 Ir al Inicio
                </a>
            </div>
        </section>

    </main>

    @include('partials.ui-feedback')

    <footer class="text-center font-orbitron text-[10px] text-slate-700 font-bold py-2">
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
            const confirmar = await window.mostrarConfirmacion({
                titulo: 'Salir del equipo',
                mensaje: '¿Deseas salir de este equipo y regresar a la pantalla de registro inicial? (Podrás volver a entrar luego usando tu PIN).',
                textoConfirmar: 'Salir del equipo',
                textoCancelar: 'Seguir aquí',
                variante: 'danger',
            });
            if (!confirmar) {
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
                ? 'min-h-[48px] py-3 rounded-lg font-orbitron text-xs sm:text-sm font-bold bg-emerald-500 text-slate-950 transition'
                : 'min-h-[48px] py-3 rounded-lg font-orbitron text-xs sm:text-sm font-bold text-slate-400 hover:text-white transition';
            document.getElementById('tab-pin').className = !esNuevo
                ? 'min-h-[48px] py-3 rounded-lg font-orbitron text-xs sm:text-sm font-bold bg-amber-400 text-slate-950 transition'
                : 'min-h-[48px] py-3 rounded-lg font-orbitron text-xs sm:text-sm font-bold text-slate-400 hover:text-white transition';
            limpiarErroresNativosForm(document.getElementById(esNuevo ? 'form-registro' : 'form-recuperar'));
        }

        function vincularValidacionNativaInput(input) {
            if (!input || input.dataset.validacionVinculada === '1') return;
            input.dataset.validacionVinculada = '1';

            input.addEventListener('invalid', () => {
                input.setAttribute('aria-invalid', 'true');
                if (input.validity.customError) return;

                if (input.validity.valueMissing) {
                    if (input.id === 'rec-pin') {
                        input.setCustomValidity('Ingresa tu PIN numérico de 4 dígitos.');
                    } else if (input.name === 'integrantes[]') {
                        input.setCustomValidity('Ingresa el nombre y apellido de este integrante.');
                    } else {
                        input.setCustomValidity('Ingresa el nombre de tu equipo.');
                    }
                } else if (input.validity.patternMismatch && input.id === 'rec-pin') {
                    input.setCustomValidity('El PIN debe contener exactamente 4 dígitos numéricos.');
                } else if (input.validity.tooShort) {
                    input.setCustomValidity(`Ingresa al menos ${input.minLength} caracteres.`);
                }
            });

            input.addEventListener('input', () => {
                input.setCustomValidity('');
                input.removeAttribute('aria-invalid');
            });
        }

        function limpiarErroresNativosForm(form) {
            if (!form) return;
            form.querySelectorAll('input').forEach(inp => {
                inp.setCustomValidity('');
                inp.removeAttribute('aria-invalid');
            });
        }

        function aplicarErrorNativoCampo(input, mensaje) {
            if (!input) return;
            input.setCustomValidity(mensaje);
            input.setAttribute('aria-invalid', 'true');
            input.focus();
            input.reportValidity();
        }

        document.querySelectorAll('#form-registro input, #form-recuperar input').forEach(vincularValidacionNativaInput);

        function agregarCampoIntegrante() {
            const contenedor = document.getElementById('lista-integrantes');
            const total = contenedor.querySelectorAll('input[name="integrantes[]"]').length;
            if (total >= maxIntegrantes) {
                window.mostrarToast(`El máximo permitido es de ${maxIntegrantes} integrantes por equipo.`, 'error');
                return;
            }
            const indice = total + 1;
            const inputId = `reg-integrante-${indice}`;

            const fila = document.createElement('div');
            fila.className = 'flex items-center gap-2';

            const label = document.createElement('label');
            label.htmlFor = inputId;
            label.className = 'sr-only';
            label.textContent = `Nombre y Apellido del Integrante ${indice}`;

            const input = document.createElement('input');
            input.type = 'text';
            input.id = inputId;
            input.name = 'integrantes[]';
            input.required = true;
            input.minLength = 3;
            input.autocomplete = 'name';
            input.placeholder = `Nombre y Apellido del Integrante ${indice} *`;
            input.className = 'w-full min-h-[48px] bg-[#0b1322] border border-slate-700 focus:border-emerald-400 rounded-xl px-3.5 py-2.5 text-base text-slate-100 placeholder-slate-500 outline-none transition';
            vincularValidacionNativaInput(input);

            const btnQuitar = document.createElement('button');
            btnQuitar.type = 'button';
            btnQuitar.setAttribute('aria-label', `Quitar integrante ${indice}`);
            btnQuitar.className = 'min-w-[48px] min-h-[48px] rounded-xl bg-slate-800/80 hover:bg-rose-500/20 border border-slate-700 hover:border-rose-400/60 text-slate-300 hover:text-rose-200 font-bold text-sm transition shrink-0';
            btnQuitar.textContent = '✕';
            btnQuitar.addEventListener('click', () => fila.remove());

            fila.append(label, input, btnQuitar);
            contenedor.appendChild(fila);
            input.focus();
        }

        document.getElementById('form-registro').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const btn = document.getElementById('btn-registrar');
            if (btn.disabled) return;

            limpiarErroresNativosForm(form);
            if (!form.checkValidity()) {
                const primeroInvalido = form.querySelector(':invalid');
                if (primeroInvalido) {
                    primeroInvalido.focus();
                    primeroInvalido.reportValidity();
                }
                return;
            }

            const formData = new FormData(form);
            const integrantesInputs = Array.from(form.querySelectorAll('input[name="integrantes[]"]'));
            const integrantesValores = integrantesInputs.map(inp => inp.value.trim()).filter(v => v !== '');
            if (integrantesValores.length < 2) {
                aplicarErrorNativoCampo(integrantesInputs[0], 'Debes ingresar al menos 2 integrantes.');
                return;
            }

            btn.disabled = true;
            const payload = {
                nombre_equipo: String(formData.get('nombre_equipo') || '').trim(),
                integrantes: integrantesValores
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
                    const keys = data.errors ? Object.keys(data.errors) : [];
                    if (keys.length > 0) primerError = data.errors[keys[0]][0];

                    const inpNombre = document.getElementById('reg-nombre-equipo');
                    const errIntKey = keys.find(k => k === 'integrantes' || k.startsWith('integrantes.'));
                    if (data.errors?.nombre_equipo || (!errIntKey && primerError.toLowerCase().includes('equipo'))) {
                        aplicarErrorNativoCampo(inpNombre, data.errors?.nombre_equipo?.[0] || primerError);
                    } else if (errIntKey || primerError.toLowerCase().includes('integrante') || primerError.toLowerCase().includes('participante') || primerError.toLowerCase().includes('nombre y apellido')) {
                        const matchIdx = errIntKey ? errIntKey.match(/^integrantes\.(\d+)$/) : null;
                        const targetInput = (matchIdx && integrantesInputs[Number(matchIdx[1])]) || integrantesInputs[0];
                        aplicarErrorNativoCampo(targetInput, data.errors?.[errIntKey]?.[0] || primerError);
                    } else {
                        aplicarErrorNativoCampo(inpNombre, primerError);
                    }
                    return;
                }
                sincronizarJuego();
            } catch (err) {
                window.mostrarToast('Error de conexión con el servidor. Intenta nuevamente.', 'error');
            } finally {
                btn.disabled = false;
            }
        });

        document.getElementById('form-recuperar').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const btn = document.getElementById('btn-recuperar');
            if (btn && btn.disabled) return;

            limpiarErroresNativosForm(form);
            if (!form.checkValidity()) {
                const primeroInvalido = form.querySelector(':invalid');
                if (primeroInvalido) {
                    primeroInvalido.focus();
                    primeroInvalido.reportValidity();
                }
                return;
            }

            if (btn) btn.disabled = true;
            const formData = new FormData(form);
            try {
                const res = await fetch('/api/recuperar-sesion', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        nombre_equipo: String(formData.get('nombre_equipo') || '').trim(),
                        pin_recuperacion: String(formData.get('pin_recuperacion') || '').trim()
                    })
                });
                const data = await res.json();
                if (!res.ok) {
                    const msg = data.errors ? Object.values(data.errors)[0][0] : (data.error || 'No se pudo recuperar el equipo.');
                    if (data.errors?.nombre_equipo) {
                        aplicarErrorNativoCampo(document.getElementById('rec-nombre-equipo'), data.errors.nombre_equipo[0]);
                    } else if (data.errors?.pin_recuperacion) {
                        aplicarErrorNativoCampo(document.getElementById('rec-pin'), data.errors.pin_recuperacion[0]);
                    } else {
                        aplicarErrorNativoCampo(document.getElementById('rec-pin'), msg);
                    }
                    return;
                }
                sincronizarJuego();
            } catch (err) {
                window.mostrarToast('Error al conectar con el servidor. Intenta de nuevo.', 'error');
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
                    activarRegistroDirectoSiAplica();
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

            const fb = document.getElementById('feedback-respuesta');
            if (fb) fb.className = 'hidden';
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
                btn.className = 'btn-opcion w-full min-h-[48px] text-left p-3.5 rounded-xl bg-[#0b1322] hover:bg-emerald-500/15 border border-slate-700 hover:border-emerald-400 transition flex items-center gap-3';
                btn.addEventListener('click', () => enviarRespuesta(op.letra, btn));

                const letra = document.createElement('span');
                letra.className = 'font-orbitron text-xs font-bold w-7 h-7 rounded-lg bg-[#162033] border border-emerald-400/50 text-emerald-300 flex items-center justify-center uppercase shrink-0';
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
                    mostrarFeedbackGlass('neutral', 'RESPUESTA YA REGISTRADA • AVANZANDO...');
                } else if (resultado.es_correcta) {
                    const rachaAct = Number(resultado.racha_actual) || 0;
                    const msgOk = rachaAct >= 3
                        ? `¡CORRECTO! +${resultado.puntos_sumados} PUNTOS • 🔥 RACHA x${rachaAct}`
                        : `¡CORRECTO! +${resultado.puntos_sumados} PUNTOS`;
                    mostrarFeedbackGlass('success', msgOk);
                } else {
                    const baseMsg = (letraElegida === 'tiempo_agotado' || resultado.fuera_de_tiempo)
                        ? '¡TIEMPO AGOTADO! AVANZANDO A LA SIGUIENTE...'
                        : 'RESPUESTA INCORRECTA • AVANZANDO...';
                    const msgErr = resultado.racha_perdida
                        ? `${baseMsg} • 💔 Racha perdida (Máxima: ${Number(resultado.racha_maxima) || 0})`
                        : baseMsg;
                    mostrarFeedbackGlass('error', msgErr);
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
                mostrarFeedbackGlass('neutral', 'SIN CONEXIÓN • REINTENTANDO...');
                setTimeout(() => {
                    preguntaActualId = null;
                    enviandoRespuesta = false;
                    sincronizarJuego();
                }, 2000);
            }
        }

        function mostrarFeedbackGlass(variante, texto) {
            const fb = document.getElementById('feedback-respuesta');
            const fbIcono = document.getElementById('feedback-respuesta-icono');
            const fbTexto = document.getElementById('feedback-respuesta-texto');
            if (!fb || !fbTexto) return;

            const estilos = {
                success: {
                    clase: 'backdrop-blur-md p-3.5 rounded-2xl border text-center font-orbitron text-xs sm:text-sm font-bold flex items-center justify-center gap-2.5 bg-emerald-500/20 border-emerald-400/80 text-emerald-100 shadow-[0_0_24px_rgba(16,185,129,0.28)]',
                    svg: '<svg class="w-5 h-5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                },
                error: {
                    clase: 'backdrop-blur-md p-3.5 rounded-2xl border text-center font-orbitron text-xs sm:text-sm font-bold flex items-center justify-center gap-2.5 bg-rose-500/20 border-rose-400/80 text-rose-100 shadow-[0_0_22px_rgba(244,63,94,0.25)]',
                    svg: '<svg class="w-5 h-5 text-rose-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                },
                neutral: {
                    clase: 'backdrop-blur-md p-3.5 rounded-2xl border text-center font-orbitron text-xs sm:text-sm font-bold flex items-center justify-center gap-2.5 bg-amber-500/20 border-amber-400/80 text-amber-100 shadow-[0_0_20px_rgba(251,191,36,0.22)]',
                    svg: '<svg class="w-5 h-5 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                }
            }[variante] || {
                clase: 'backdrop-blur-md p-3.5 rounded-2xl border text-center font-orbitron text-xs sm:text-sm font-bold flex items-center justify-center gap-2.5 bg-amber-500/20 border-amber-400/80 text-amber-100',
                svg: ''
            };

            fb.className = estilos.clase;
            if (fbIcono) fbIcono.innerHTML = estilos.svg;
            fbTexto.textContent = texto;
        }

        function mostrarAvisoNeutral(fb, texto) {
            mostrarFeedbackGlass('neutral', texto);
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

        function dispararEscudoSalidaSiActiva() {
            if (!esRecargaPagina && enPreguntaActiva && preguntaActualId && !enviandoRespuesta) {
                reportarSalidaPantalla();
            }
        }

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                dispararEscudoSalidaSiActiva();
            }
        });

        window.addEventListener('blur', dispararEscudoSalidaSiActiva);
        window.addEventListener('pagehide', dispararEscudoSalidaSiActiva);

        document.addEventListener('keyup', (e) => {
            const key = String(e.key || '');
            const capturaWindowsMac = (e.metaKey || e.ctrlKey) && e.shiftKey && key.toLowerCase() === 's';
            if (key === 'PrintScreen' || capturaWindowsMac) {
                dispararEscudoSalidaSiActiva();
            }
        });

        function desactivarCortina() {
            document.getElementById('cortina-anti-ia').classList.add('hidden');
            preguntaActualId = null;
            enviandoRespuesta = false;
            sincronizarJuego();
        }

        let registroAutoActivado = false;

        function activarRegistroDirectoSiAplica() {
            if (registroAutoActivado) {
                return;
            }
            registroAutoActivado = true;
            cambiarTab('nuevo');
            const inputEquipo = document.getElementById('reg-nombre-equipo');
            if (inputEquipo && !inputEquipo.value) {
                inputEquipo.focus();
            }
        }

        sincronizarJuego();
    </script>
</body>
</html>