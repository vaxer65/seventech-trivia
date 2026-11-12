<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seven Tech - Carrera en Vivo (Espectador)</title>
    @include('partials.cyberpunk-head')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        .finish-line {
            background-image: conic-gradient(#07101E 90deg, #00E5FF 90deg 180deg, #07101E 180deg 270deg, #00E5FF 270deg);
            background-size: 12px 12px;
            opacity: 0.35;
        }
        .track-grid {
            background-image: linear-gradient(to right, rgba(0, 229, 255, 0.08) 1px, transparent 1px);
            background-size: 25% 100%;
        }
    </style>
</head>
<body class="circuit-bg min-h-screen p-4 md:p-6">

    <!-- BANNER DE RECONEXIÓN EN PANTALLA GIGANTE (U3) -->
    <div id="banner-conexion-espectador" class="hidden fixed top-0 inset-x-0 z-50 bg-rose-600/95 text-white font-orbitron text-sm md:text-base font-bold text-center py-2.5 px-4 shadow-lg">
        📡 RECONECTANDO CON EL SERVIDOR DE TELEMETRÍA EN VIVO...
    </div>

    <!-- CABECERA HUD DE TRANSMISIÓN EN VIVO -->
    <header class="max-w-7xl mx-auto rounded-xl p-4 mb-6 flex flex-wrap items-center justify-between neon-border backdrop-blur-md">
        <div class="flex items-center gap-4">
            <a href="/" title="Ir al Portal Principal">
                <img src="/favicon.svg" alt="Seven Tech" class="w-14 h-14 rounded-xl border border-[#00E5FF] shadow-[0_0_15px_rgba(0,229,255,0.4)]">
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <a href="/"><h1 class="cyber-title text-2xl md:text-4xl">SEVEN TECH</h1></a>
                    <span id="badge-estado" class="px-3 py-1 text-xs md:text-sm font-bold uppercase rounded bg-cyan-500/20 text-[#00E5FF] border border-[#00E5FF]">
                        CARGANDO...
                    </span>
                </div>
                <p id="modulo-actual" class="font-orbitron text-sm md:text-base text-slate-300 mt-1">Módulo: Comunidad Tecnológica</p>
            </div>
        </div>

        <!-- TELEMETRÍA EN VIVO + RELOJ GLOBAL DEL TORNEO -->
        <div class="flex items-center gap-5 sm:gap-7 mt-3 sm:mt-0">
            <div class="text-center bg-[#07101E] px-4 py-1.5 rounded-xl border border-[#00E5FF]/40">
                <p class="font-orbitron text-xs text-slate-400 uppercase tracking-wider">⏱️ Tiempo Torneo</p>
                <p id="reloj-global-espectador" class="font-orbitron text-2xl md:text-3xl font-bold text-[#00E5FF]">--:--</p>
            </div>
            <div class="text-right">
                <p class="font-orbitron text-xs text-slate-400 uppercase">Equipos</p>
                <p id="total-equipos" class="font-orbitron text-2xl md:text-3xl font-bold text-white">0</p>
            </div>
            <div class="h-10 w-px bg-slate-700 hidden sm:block"></div>
            <div class="text-right hidden sm:block">
                <p class="font-orbitron text-xs text-slate-400 uppercase">Puntero Actual</p>
                <p id="lider-actual" class="text-xl md:text-2xl font-bold text-amber-400">Esperando salida...</p>
            </div>
        </div>
    </header>

    <!-- BANNER QR DINÁMICO (SOLO EN ESTADO 'REGISTRO') -->
    <section id="panel-qr-registro" class="max-w-7xl mx-auto mb-6 neon-border p-4 rounded-xl flex flex-col md:flex-row items-center justify-between gap-4 bg-[#0F2342]/95">
        <div class="space-y-1 text-center md:text-left">
            <span class="font-orbitron text-[10px] uppercase px-2.5 py-0.5 bg-[#00E5FF]/20 text-[#00E5FF] border border-[#00E5FF]/50 rounded">
                 // ACCESO ABIERTO A CONCURSANTES
            </span>
            <h2 class="text-2xl md:text-3xl font-bold text-white">¡ESCANEA EL QR CON TU MÓVIL PARA REGISTRAR TU EQUIPO!</h2>
            <p class="text-slate-300 text-base">O ingresa directamente desde tu navegador a: <b id="url-jugar-texto" class="font-orbitron text-[#00E5FF] underline">{{ $urlRegistroQr ?? url('/jugar?registro=1') }}</b></p>
            <p id="aviso-qr-localhost" class="{{ !empty($mostrarAvisoLocalhost) ? '' : 'hidden ' }}text-xs font-orbitron text-amber-300 bg-amber-500/15 border border-amber-400/50 rounded-lg px-3 py-1.5 mt-1">
                ⚠️ No se detectó una IP LAN activa para este servidor local: los celulares no podrán entrar con <b>localhost</b>. Inicia con <code>php artisan serve --host=0.0.0.0 --port=8000</code> conectado a la red Wi-Fi o configura <code>APP_URL</code>.
            </p>
        </div>
        <div class="flex items-center gap-4 bg-[#07101E] p-3 rounded-xl border border-[#00E5FF]">
            <div id="qrcode" class="bg-white p-2 rounded-lg"></div>
            <div class="hidden sm:block pr-2">
                <p class="font-orbitron text-[10px] text-slate-400 uppercase">Integrantes</p>
                <p class="text-lg font-bold text-[#00E5FF]">Mín. 2 / Máx. 5</p>
                <p class="font-orbitron text-[10px] text-emerald-400 mt-1">● Sorteo Aleatorio Activo</p>
            </div>
        </div>
    </section>

    <!-- PODIO DE GANADORES CON LAURELES ESTILO CYBERPUNK -->
    <section id="seccion-podio" class="hidden max-w-7xl mx-auto mb-8 neon-border p-6 md:p-8 rounded-2xl bg-gradient-to-b from-[#0F2342] to-[#07101E] text-center">
        <p class="font-orbitron text-xs uppercase tracking-[0.3em] text-[#00E5FF] mb-1">// RESULTADOS OFICIALES DEL TORNEO //</p>
        <h2 class="cyber-title text-3xl md:text-5xl mb-8">PODIO DE CAMPEONES</h2>
        <div id="contenedor-laureles-podio" class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center"></div>
    </section>

    <main class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- COLUMNA IZQUIERDA (2/3): PISTA DE CARRERAS 2D PRO -->
        <section class="lg:col-span-2 rounded-xl p-5 neon-border flex flex-col">
            <div class="flex flex-wrap items-center justify-between mb-4 gap-2">
                <h2 class="text-xl font-bold text-[#00E5FF] tracking-wide flex items-center gap-2">
                    🏁 TELEMETRÍA DE CARRERA EN TIEMPO REAL
                </h2>
                <span class="font-orbitron text-[10px] px-2.5 py-1 rounded bg-[#07101E] text-slate-300 border border-slate-800">
                    POSICIÓN = PUNTAJE + VELOCIDAD (MS)
                </span>
            </div>

            <!-- Cabecera de Etapas con colores neón -->
            <div class="grid grid-cols-4 text-center font-orbitron text-xs md:text-sm font-bold uppercase mb-4 bg-[#07101E] p-3 rounded-xl border border-slate-800 shadow-inner">
                <div class="text-emerald-400 border-r border-slate-800 flex flex-col items-center">
                    <span>🟢 ETAPA 1</span>
                    <span class="text-xs text-slate-300 font-normal">CALENTAMIENTO</span>
                </div>
                <div class="text-[#00E5FF] border-r border-slate-800 flex flex-col items-center">
                    <span>🔵 ETAPA 2</span>
                    <span class="text-xs text-slate-300 font-normal">VEL. CRUCERO</span>
                </div>
                <div class="text-amber-400 border-r border-slate-800 flex flex-col items-center">
                    <span>🟠 ETAPA 3</span>
                    <span class="text-xs text-slate-300 font-normal">RECTA PRO</span>
                </div>
                <div class="text-rose-400 flex flex-col items-center">
                    <span>🏆 META FINAL</span>
                    <span class="text-xs text-slate-300 font-normal">BANDERA A CUADROS</span>
                </div>
            </div>

            <!-- Carriles (U2: mayor aprovechamiento vertical en pantallas de auditorio) -->
            <div id="pista-carriles" class="space-y-3 flex-1 overflow-y-auto pr-1" style="max-height: 70vh;">
                <div class="text-center py-12 text-slate-400 text-lg">
                    Esperando a que los equipos escaneen el QR o entren a <b>/jugar</b>...
                </div>
            </div>
        </section>

        <!-- COLUMNA DERECHA (1/3): TABLA DE POSICIONES EN VIVO -->
        <section class="rounded-xl p-5 neon-border flex flex-col">
            <h2 class="text-xl md:text-2xl font-bold text-white mb-1 flex items-center justify-between">
                <span>⚡ CLASIFICACIÓN GENERAL</span>
                <span id="indicador-live" class="font-orbitron text-xs text-[#00E5FF] animate-pulse">● LIVE</span>
            </h2>
            <p class="font-orbitron text-xs text-slate-300 mb-4">En empate de puntos, gana quien tenga menor tiempo.</p>

            <div id="tabla-posiciones" class="space-y-2.5 overflow-y-auto flex-1" style="max-height: 72vh;"></div>
        </section>

    </main>

    <script>
        const urlJugar = @json($urlRegistroQr ?? (rtrim((string) config('app.url'), '/') . '/jugar?registro=1'));
        const mostrarAvisoLocalhost = @json((bool) ($mostrarAvisoLocalhost ?? false));
        document.getElementById('url-jugar-texto').textContent = urlJugar;
        if (mostrarAvisoLocalhost) {
            document.getElementById('aviso-qr-localhost')?.classList.remove('hidden');
        } else {
            document.getElementById('aviso-qr-localhost')?.classList.add('hidden');
        }
        new QRCode(document.getElementById("qrcode"), {
            text: urlJugar,
            width: 96,
            height: 96,
            colorDark : "#07101E",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.M
        });

        let confetiLanzado = false;
        let segundosGlobalesLocal = null;

        function formatearMinutosSegundos(seg) {
            if (seg === null || seg === undefined) return '--:--';
            const m = Math.floor(seg / 60).toString().padStart(2, '0');
            const s = Math.floor(seg % 60).toString().padStart(2, '0');
            return `${m}:${s}`;
        }

        // Descontar segundo a segundo el reloj global suavemente en pantalla
        setInterval(() => {
            if (segundosGlobalesLocal !== null && segundosGlobalesLocal > 0) {
                segundosGlobalesLocal--;
                document.getElementById('reloj-global-espectador').textContent = formatearMinutosSegundos(segundosGlobalesLocal);
            }
        }, 1000);

        function renderizarInsigniaLaurel(equipo, puesto) {
            const configPuesto = {
                1: { titulo: '1° LUGAR • CAMPEÓN', color: '#FCEE09', bg: 'bg-[#FCEE09]/10 border-[#FCEE09]', escala: 'md:scale-105 shadow-[0_0_30px_rgba(252,238,9,0.25)]' },
                2: { titulo: '2° LUGAR • SUBCAMPEÓN', color: '#00E5FF', bg: 'bg-[#00E5FF]/10 border-[#00E5FF]/60', escala: '' },
                3: { titulo: '3° LUGAR • PODIO TECH', color: '#10B981', bg: 'bg-emerald-500/10 border-emerald-400/60', escala: '' }
            }[puesto];

            return `
            <div class="p-5 rounded-2xl border ${configPuesto.bg} ${configPuesto.escala} transition flex flex-col items-center justify-center">
                <span class="font-orbitron text-xs md:text-sm font-bold tracking-widest uppercase px-3 py-1 rounded-full bg-[#07101E] mb-3" style="color: ${configPuesto.color}; border: 1px solid ${configPuesto.color};">
                    ${configPuesto.titulo}
                </span>
                <div class="flex items-center justify-center gap-2 sm:gap-3 my-1 w-full">
                    <svg viewBox="0 0 60 120" class="w-10 h-20 sm:w-12 sm:h-24 shrink-0" style="fill: ${configPuesto.color};">
                        <path d="M48,108 C22,92 12,64 18,28 C20,18 25,10 32,4 L36,9 C28,18 24,32 24,50 C24,72 34,92 52,103 Z"/>
                        <polygon points="44,104 28,98 34,90 48,96"/><polygon points="34,88 18,80 24,72 38,80"/>
                        <polygon points="28,70 12,60 19,52 32,62"/><polygon points="26,52 12,40 20,33 31,44"/>
                        <polygon points="28,34 17,22 25,16 34,27"/><polygon points="33,18 26,7 34,3 39,13"/>
                    </svg>
                    <div class="text-center px-1">
                        <h3 class="text-2xl sm:text-4xl font-bold uppercase tracking-wide text-white leading-tight" data-nombre-equipo="${equipo.id}"></h3>
                        <p class="font-orbitron text-base sm:text-lg font-bold uppercase tracking-widest mt-1.5" style="color: ${configPuesto.color};">
                            ${equipo.puntaje_total} PTS • ${equipo.tiempo_segundos}S
                        </p>
                        <p class="font-orbitron text-xs sm:text-sm text-slate-300 uppercase mt-1">${equipo.aciertos_totales} ACIERTOS</p>
                    </div>
                    <svg viewBox="0 0 60 120" class="w-10 h-20 sm:w-12 sm:h-24 shrink-0" style="fill: ${configPuesto.color}; transform: scaleX(-1);">
                        <path d="M48,108 C22,92 12,64 18,28 C20,18 25,10 32,4 L36,9 C28,18 24,32 24,50 C24,72 34,92 52,103 Z"/>
                        <polygon points="44,104 28,98 34,90 48,96"/><polygon points="34,88 18,80 24,72 38,80"/>
                        <polygon points="28,70 12,60 19,52 32,62"/><polygon points="26,52 12,40 20,33 31,44"/>
                        <polygon points="28,34 17,22 25,16 34,27"/><polygon points="33,18 26,7 34,3 39,13"/>
                    </svg>
                </div>
            </div>`;
        }

        // Los nombres de equipo vienen del público: se insertan SIEMPRE con textContent, nunca como HTML
        function pintarNombres(contenedor, equipos, formato = n => n) {
            const porId = new Map(equipos.map(eq => [String(eq.id), eq.nombre_equipo]));
            contenedor.querySelectorAll('[data-nombre-equipo]').forEach(el => {
                el.textContent = formato(porId.get(el.dataset.nombreEquipo) ?? '');
            });
        }

        let fallosConexionEspectador = 0;
        let syncTimerEspectador = null;
        let actualizandoEspectador = false;
        let descargandoEspectador = false;

        window.addEventListener('beforeunload', () => { descargandoEspectador = true; });

        function actualizarEstadoConexionEspectador(conectado) {
            if (descargandoEspectador) return;
            const banner = document.getElementById('banner-conexion-espectador');
            if (banner) banner.classList.toggle('hidden', conectado);
            const live = document.getElementById('indicador-live');
            if (live) {
                live.textContent = conectado ? '● LIVE' : '⚠️ RECONECTANDO...';
                live.className = conectado
                    ? 'font-orbitron text-xs text-[#00E5FF] animate-pulse'
                    : 'font-orbitron text-xs text-rose-400 animate-pulse';
            }
        }

        window.addEventListener('offline', () => actualizarEstadoConexionEspectador(false));
        window.addEventListener('online', () => {
            fallosConexionEspectador = 0;
            actualizarEstadoConexionEspectador(true);
            actualizarCarrera();
        });

        async function actualizarCarrera() {
            if (actualizandoEspectador || descargandoEspectador) return;
            actualizandoEspectador = true;
            if (syncTimerEspectador) clearTimeout(syncTimerEspectador);

            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 8000);

            try {
                const res = await fetch('/api/carrera-en-vivo', {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                    signal: controller.signal
                });
                clearTimeout(timeoutId);
                if (!res.ok) {
                    fallosConexionEspectador++;
                    if (fallosConexionEspectador >= 6) actualizarEstadoConexionEspectador(false);
                    return;
                }
                fallosConexionEspectador = 0;
                actualizarEstadoConexionEspectador(true);
                const data = await res.json();

                const estadoRaw = data.evento.estado_juego;
                const estado = estadoRaw.toUpperCase();

                // Sincronizar Reloj Global
                segundosGlobalesLocal = data.evento.segundos_globales_restantes;
                const elReloj = document.getElementById('reloj-global-espectador');
                if (estadoRaw === 'en_curso' && segundosGlobalesLocal !== null) {
                    elReloj.textContent = formatearMinutosSegundos(segundosGlobalesLocal);
                } else if (estadoRaw === 'registro') {
                    elReloj.textContent = (data.evento.tiempo_total_minutos || 15) + ':00';
                } else {
                    elReloj.textContent = estado;
                }

                // Mostrar/Ocultar QR
                const panelQr = document.getElementById('panel-qr-registro');
                panelQr.classList.toggle('hidden', estadoRaw !== 'registro');

                // Mostrar/Ocultar Podio
                const seccionPodio = document.getElementById('seccion-podio');
                const contenedorLaureles = document.getElementById('contenedor-laureles-podio');

                if (estadoRaw === 'finalizado' && data.equipos.length > 0) {
                    seccionPodio.classList.remove('hidden');
                    const top3 = data.equipos.slice(0, 3);
                    let htmlPodio = '';
                    if (top3[1]) htmlPodio += renderizarInsigniaLaurel(top3[1], 2);
                    if (top3[0]) htmlPodio += renderizarInsigniaLaurel(top3[0], 1);
                    if (top3[2]) htmlPodio += renderizarInsigniaLaurel(top3[2], 3);
                    contenedorLaureles.innerHTML = htmlPodio;
                    pintarNombres(contenedorLaureles, data.equipos, n => `"${n}"`);

                    if (!confetiLanzado && typeof confetti === 'function') {
                        confetiLanzado = true;
                        confetti({ particleCount: 130, spread: 85, origin: { y: 0.6 } });
                    }
                } else {
                    seccionPodio.classList.add('hidden');
                    confetiLanzado = false;
                }

                const badge = document.getElementById('badge-estado');
                badge.textContent = estado === 'DESEMPATE' ? '🔥 MUERTE SÚBITA' : estado.replace('_', ' ');
                badge.className = estado === 'DESEMPATE'
                    ? 'font-orbitron px-3 py-1 text-xs md:text-sm font-bold uppercase rounded bg-rose-600 text-white animate-pulse'
                    : 'font-orbitron px-3 py-1 text-xs md:text-sm font-bold uppercase rounded bg-cyan-500/20 text-[#00E5FF] border border-[#00E5FF]';

                if (data.evento.modulo_activo) {
                    document.getElementById('modulo-actual').textContent = 'Categoría: ' + data.evento.modulo_activo.nombre;
                }
                document.getElementById('total-equipos').textContent = data.equipos.length;
                document.getElementById('lider-actual').textContent = data.equipos.length > 0 ? '👑 ' + data.equipos[0].nombre_equipo : 'Sin equipos';

                const pista = document.getElementById('pista-carriles');
                const tabla = document.getElementById('tabla-posiciones');

                if (data.equipos.length === 0) {
                    pista.innerHTML = `<div class="text-center py-12 text-slate-400 text-lg">Aún no hay equipos en la línea de salida. ¡Escaneen el QR para unirse!</div>`;
                    tabla.innerHTML = `<div class="text-center py-8 font-orbitron text-slate-500 text-xs">SIN REGISTROS TODAVÍA</div>`;
                    return;
                }

                pista.innerHTML = data.equipos.map((eq, idx) => {
                    const esLider = idx === 0 && eq.puntaje_total > 0;
                    const rachaActual = Number(eq.racha_actual) || 0;
                    const rachaMaxima = Number(eq.racha_maxima) || 0;
                    const badgeRacha = rachaActual >= 3
                        ? `<span class="font-orbitron text-orange-300 font-bold text-xs px-2.5 py-0.5 rounded-full bg-orange-500/20 border border-orange-400 shadow-[0_0_12px_rgba(251,146,60,0.45)] animate-pulse">🔥 RACHA x${rachaActual}</span>`
                        : (rachaMaxima >= 3
                            ? `<span class="font-orbitron text-amber-200/80 font-bold text-[11px] px-2 py-0.5 rounded bg-slate-800/90 border border-slate-600">🏅 Racha máx: ${rachaMaxima}</span>`
                            : '');
                    const etiquetaFase = eq.en_desempate ? '🔥 DESEMPATE' : (eq.finalizo_carrera ? '🏁 META COMPLETADA' : `ETAPA ${eq.fase_actual}`);
                    const leftPos = Math.min(92, Math.max(3, eq.porcentaje_pista));

                    return `
                    <div class="bg-[#07101E]/95 border ${esLider ? 'border-amber-400 shadow-[0_0_18px_rgba(251,191,36,0.2)]' : 'border-slate-800'} rounded-xl p-3.5 relative overflow-hidden transition">
                        <div class="flex flex-wrap justify-between items-center mb-2.5 z-10 relative gap-2">
                            <div class="flex items-center gap-2.5">
                                <span class="font-orbitron font-bold text-sm md:text-base px-2.5 py-0.5 rounded ${esLider ? 'bg-amber-400 text-[#07101E]' : 'bg-slate-800 text-[#00E5FF]'}">#${eq.posicion}</span>
                                <span class="font-bold text-xl md:text-2xl text-white tracking-wide" data-nombre-equipo="${eq.id}"></span>
                                ${esLider ? '<span class="font-orbitron text-amber-400 font-bold text-xs px-2 py-0.5 rounded bg-amber-500/10 border border-amber-400/40">👑 LÍDER</span>' : ''}
                                ${badgeRacha}
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="font-orbitron text-xs md:text-sm font-bold px-2.5 py-0.5 rounded bg-[#0F2342] text-[#00E5FF] border border-[#00E5FF]/30">
                                    ${etiquetaFase} • ${eq.preguntas_contestadas}/${eq.total_preguntas}
                                </span>
                                <span class="font-orbitron font-bold text-emerald-400 text-lg md:text-xl">${eq.puntaje_total} PTS</span>
                            </div>
                        </div>

                        <!-- Pista con textura de cuadrícula y línea de meta a cuadros -->
                        <div class="relative h-12 bg-[#0B1930] rounded-xl border border-slate-700/80 flex items-center overflow-hidden track-grid">
                            <!-- Línea central de carril -->
                            <div class="absolute inset-x-0 h-px bg-slate-700/60"></div>

                            <!-- Bandera a cuadros en la meta derecha -->
                            <div class="absolute right-0 top-0 bottom-0 w-6 finish-line border-l border-[#00E5FF]/40"></div>

                            <!-- Barra de impulso láser -->
                            <div class="h-3 rounded-full transition-all duration-700 ml-2 relative z-10"
                                 style="width: ${leftPos}%; background: linear-gradient(90deg, transparent, ${eq.color_carril}); box-shadow: 0 0 14px ${eq.color_carril};">
                            </div>

                            <!-- Avatar Robot Corredor -->
                            <div class="absolute top-1.5 robot-transition z-20 flex items-center justify-center w-9 h-9 rounded-full border-2 bg-[#07101E] ${esLider ? 'leader-pulse' : ''}"
                                 style="left: calc(${leftPos}% - 14px); border-color: ${eq.color_carril}; box-shadow: 0 0 16px ${eq.color_carril};">
                                <img src="/favicon.svg" class="w-7 h-7 rounded-full" alt="Bot">
                            </div>
                        </div>
                    </div>`;
                }).join('');

                tabla.innerHTML = data.equipos.map((eq, idx) => {
                    const medalla = idx === 0 ? '🥇' : (idx === 1 ? '🥈' : (idx === 2 ? '🥉' : `#${eq.posicion}`));
                    return `
                    <div class="flex items-center justify-between p-3.5 rounded-xl bg-[#07101E]/90 border ${idx === 0 && eq.puntaje_total > 0 ? 'border-amber-400/70' : (eq.en_desempate ? 'border-rose-500' : 'border-slate-800')}">
                        <div class="flex items-center gap-3">
                            <span class="font-orbitron text-xl md:text-2xl font-bold w-9 text-center text-[#00E5FF]">${medalla}</span>
                            <div>
                                <p class="font-bold text-lg md:text-xl text-white leading-tight" data-nombre-equipo="${eq.id}"></p>
                                <p class="font-orbitron text-xs md:text-sm text-slate-300 mt-0.5">✅ ${eq.aciertos_totales} ACIERTOS • ⏱️ ${eq.tiempo_segundos}S</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="font-orbitron text-xl md:text-2xl font-bold text-[#00E5FF]">${eq.puntaje_total}</span>
                            <span class="font-orbitron block text-xs text-slate-300 uppercase">PUNTOS</span>
                        </div>
                    </div>`;
                }).join('');

                pintarNombres(pista, data.equipos);
                pintarNombres(tabla, data.equipos);
            } catch (e) {
                clearTimeout(timeoutId);
                if (descargandoEspectador) return;
                fallosConexionEspectador++;
                if (fallosConexionEspectador >= 6 || !navigator.onLine) {
                    actualizarEstadoConexionEspectador(false);
                }
                console.error('Error sincronizando carrera:', e);
            } finally {
                actualizandoEspectador = false;
                if (!descargandoEspectador) {
                    const delay = fallosConexionEspectador > 0 ? Math.min(6000, 2000 + (fallosConexionEspectador * 1000)) : 2000;
                    syncTimerEspectador = setTimeout(actualizarCarrera, delay);
                }
            }
        }

        actualizarCarrera();
    </script>
</body>
</html>