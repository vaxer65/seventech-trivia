<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seven Tech - Carrera en Vivo (Espectador)</title>
    @include('partials.cyberpunk-head')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
</head>
<body class="circuit-bg min-h-screen p-4 md:p-6">

    <!-- CABECERA OFICIAL SEVEN TECH -->
    <header class="max-w-7xl mx-auto rounded-xl p-4 mb-6 flex flex-wrap items-center justify-between neon-border backdrop-blur-md">
        <div class="flex items-center gap-4">
            <a href="/" title="Ir al Portal Principal">
                <img src="/favicon.svg" alt="Seven Tech" class="w-14 h-14 rounded-xl border border-[#00E5FF]">
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <a href="/"><h1 class="cyber-title text-2xl md:text-3xl">SEVEN TECH</h1></a>
                    <span id="badge-estado" class="px-3 py-1 text-[11px] font-bold uppercase rounded bg-cyan-500/20 text-[#00E5FF] border border-[#00E5FF]">
                        CARGANDO...
                    </span>
                </div>
                <p id="modulo-actual" class="font-orbitron text-xs text-slate-300 mt-1">Módulo: Comunidad Tecnológica</p>
            </div>
        </div>

        <div class="flex items-center gap-6 mt-3 sm:mt-0">
            <div class="text-right">
                <p class="font-orbitron text-[10px] text-slate-400 uppercase">Equipos en Pista</p>
                <p id="total-equipos" class="text-3xl font-bold text-[#00E5FF]">0</p>
            </div>
            <div class="h-10 w-px bg-slate-700"></div>
            <div class="text-right">
                <p class="font-orbitron text-[10px] text-slate-400 uppercase">Líder Actual</p>
                <p id="lider-actual" class="text-2xl font-bold text-amber-400">Esperando salida...</p>
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
            <p class="text-slate-300 text-base">O ingresa directamente desde tu navegador a: <b id="url-jugar-texto" class="font-orbitron text-[#00E5FF] underline"></b></p>
        </div>
        <div class="flex items-center gap-4 bg-[#07101E] p-3 rounded-xl border border-[#00E5FF]">
            <div id="qrcode" class="bg-white p-2 rounded-lg"></div>
            <div class="hidden sm:block pr-2">
                <p class="font-orbitron text-[10px] text-slate-400 uppercase">Límite por grupo</p>
                <p class="text-lg font-bold text-[#00E5FF]">Mín. 2 / Máx. 5</p>
                <p class="font-orbitron text-[10px] text-emerald-400 mt-1">● Servidor Online</p>
            </div>
        </div>
    </section>

    <!-- PODIO DE GANADORES CON LAURELES ESTILO CYBERPUNK (SE MUESTRA AL FINALIZAR LA CARRERA) -->
    <section id="seccion-podio" class="hidden max-w-7xl mx-auto mb-8 neon-border p-6 md:p-8 rounded-2xl bg-gradient-to-b from-[#0F2342] to-[#07101E] text-center">
        <p class="font-orbitron text-xs uppercase tracking-[0.3em] text-[#00E5FF] mb-1">// RESULTADOS OFICIALES DEL TORNEO //</p>
        <h2 class="cyber-title text-3xl md:text-5xl mb-8">PODIO DE CAMPEONES</h2>

        <div id="contenedor-laureles-podio" class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
            <!-- Se inyectan los 3 ganadores con sus coronas de laurel geométricas -->
        </div>
    </section>

    <main class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- COLUMNA IZQUIERDA (2/3): PISTA DE CARRERAS 2D -->
        <section class="lg:col-span-2 rounded-xl p-5 neon-border flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-[#00E5FF] tracking-wide flex items-center gap-2">
                    🏁 PISTA DE CARRERA TECNOLÓGICA EN VIVO
                </h2>
                <span class="font-orbitron text-[10px] text-slate-300">AVANCE: PUNTAJE + VELOCIDAD (MS)</span>
            </div>

            <div class="grid grid-cols-4 text-center font-orbitron text-[11px] font-bold uppercase mb-3 bg-[#07101E] p-2.5 rounded-lg border border-slate-800">
                <div class="text-emerald-400 border-r border-slate-800">🟢 Etapa 1: Fácil</div>
                <div class="text-[#00E5FF] border-r border-slate-800">🔵 Etapa 2: Media</div>
                <div class="text-amber-400 border-r border-slate-800">🟠 Etapa 3: Pro</div>
                <div class="text-rose-400">🏆 META / FINAL</div>
            </div>

            <div id="pista-carriles" class="space-y-4 flex-1 overflow-y-auto pr-1" style="max-height: 58vh;">
                <div class="text-center py-12 text-slate-400 text-lg">
                    Esperando a que los equipos escaneen el QR o entren a <b>/jugar</b>...
                </div>
            </div>
        </section>

        <!-- COLUMNA DERECHA (1/3): TABLA DE POSICIONES -->
        <section class="rounded-xl p-5 neon-border flex flex-col">
            <h2 class="text-xl font-bold text-white mb-1 flex items-center justify-between">
                <span>⚡ TABLA DE POSICIONES</span>
                <span class="font-orbitron text-[10px] text-[#00E5FF]">LIVE TELEMETRY</span>
            </h2>
            <p class="font-orbitron text-[10px] text-slate-400 mb-4">Desempate automático por menor tiempo acumulado.</p>

            <div id="tabla-posiciones" class="space-y-2.5 overflow-y-auto flex-1" style="max-height: 60vh;">
            </div>
        </section>

    </main>

    <script>
        const urlJugar = window.location.origin + '/jugar';
        document.getElementById('url-jugar-texto').textContent = urlJugar;
        new QRCode(document.getElementById("qrcode"), {
            text: urlJugar,
            width: 96,
            height: 96,
            colorDark : "#07101E",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.M
        });

        let confetiLanzado = false;

        // Generador de Corona de Laureles Geométrica estilo Cyberpunk 2077 Accolade
        function renderizarInsigniaLaurel(equipo, puesto) {
            const configPuesto = {
                1: { titulo: '1° LUGAR • CAMPEÓN', color: '#FCEE09', bg: 'bg-[#FCEE09]/10 border-[#FCEE09]', escala: 'md:scale-105 shadow-[0_0_30px_rgba(252,238,9,0.25)]' },
                2: { titulo: '2° LUGAR • SUBCAMPEÓN', color: '#00E5FF', bg: 'bg-[#00E5FF]/10 border-[#00E5FF]/60', escala: '' },
                3: { titulo: '3° LUGAR • PODIO TECH', color: '#10B981', bg: 'bg-emerald-500/10 border-emerald-400/60', escala: '' }
            }[puesto];

            return `
            <div class="p-5 rounded-2xl border ${configPuesto.bg} ${configPuesto.escala} transition flex flex-col items-center justify-center">
                <span class="font-orbitron text-[10px] font-bold tracking-widest uppercase px-3 py-0.5 rounded-full bg-[#07101E] mb-3" style="color: ${configPuesto.color}; border: 1px solid ${configPuesto.color};">
                    ${configPuesto.titulo}
                </span>

                <div class="flex items-center justify-center gap-2 sm:gap-3 my-1 w-full">
                    <!-- Rama de Laurel Izquierda (Geométrica estilo Cyberpunk) -->
                    <svg viewBox="0 0 60 120" class="w-10 h-20 sm:w-12 sm:h-24 shrink-0" style="fill: ${configPuesto.color};">
                        <path d="M48,108 C22,92 12,64 18,28 C20,18 25,10 32,4 L36,9 C28,18 24,32 24,50 C24,72 34,92 52,103 Z"/>
                        <polygon points="44,104 28,98 34,90 48,96"/>
                        <polygon points="34,88 18,80 24,72 38,80"/>
                        <polygon points="28,70 12,60 19,52 32,62"/>
                        <polygon points="26,52 12,40 20,33 31,44"/>
                        <polygon points="28,34 17,22 25,16 34,27"/>
                        <polygon points="33,18 26,7 34,3 39,13"/>
                    </svg>

                    <!-- Texto Central: Rajdhani Bold arriba entre comillas + Orbitron abajo -->
                    <div class="text-center px-1">
                        <h3 class="text-2xl sm:text-3xl font-bold uppercase tracking-wide text-white leading-tight" style="font-family: 'Rajdhani', sans-serif;">
                            "${equipo.nombre_equipo}"
                        </h3>
                        <p class="font-orbitron text-xs sm:text-sm font-semibold uppercase tracking-widest mt-1.5" style="color: ${configPuesto.color};">
                            ${equipo.puntaje_total} PTS • ${equipo.tiempo_segundos}S
                        </p>
                        <p class="font-orbitron text-[10px] text-slate-400 uppercase mt-1">
                            ${equipo.aciertos_totales} ACIERTOS
                        </p>
                    </div>

                    <!-- Rama de Laurel Derecha (Espejo) -->
                    <svg viewBox="0 0 60 120" class="w-10 h-20 sm:w-12 sm:h-24 shrink-0" style="fill: ${configPuesto.color}; transform: scaleX(-1);">
                        <path d="M48,108 C22,92 12,64 18,28 C20,18 25,10 32,4 L36,9 C28,18 24,32 24,50 C24,72 34,92 52,103 Z"/>
                        <polygon points="44,104 28,98 34,90 48,96"/>
                        <polygon points="34,88 18,80 24,72 38,80"/>
                        <polygon points="28,70 12,60 19,52 32,62"/>
                        <polygon points="26,52 12,40 20,33 31,44"/>
                        <polygon points="28,34 17,22 25,16 34,27"/>
                        <polygon points="33,18 26,7 34,3 39,13"/>
                    </svg>
                </div>
            </div>`;
        }

        async function actualizarCarrera() {
            try {
                const res = await fetch('/api/carrera-en-vivo');
                const data = await res.json();

                const estadoRaw = data.evento.estado_juego;
                const estado = estadoRaw.toUpperCase();

                // 1. Mostrar/Ocultar QR de Registro
                const panelQr = document.getElementById('panel-qr-registro');
                if (estadoRaw === 'registro') {
                    panelQr.classList.remove('hidden');
                } else {
                    panelQr.classList.add('hidden');
                }

                // 2. Mostrar/Ocultar Podio de Ganadores con Laureles al finalizar
                const seccionPodio = document.getElementById('seccion-podio');
                const contenedorLaureles = document.getElementById('contenedor-laureles-podio');

                if (estadoRaw === 'finalizado' && data.equipos.length > 0) {
                    seccionPodio.classList.remove('hidden');
                    const top3 = data.equipos.slice(0, 3);
                    // Orden visual: 2° Lugar (Izquierda), 1° Lugar Campeón (Centro), 3° Lugar (Derecha)
                    let htmlPodio = '';
                    if (top3[1]) htmlPodio += renderizarInsigniaLaurel(top3[1], 2);
                    if (top3[0]) htmlPodio += renderizarInsigniaLaurel(top3[0], 1);
                    if (top3[2]) htmlPodio += renderizarInsigniaLaurel(top3[2], 3);
                    contenedorLaureles.innerHTML = htmlPodio;

                    if (!confetiLanzado && typeof confetti === 'function') {
                        confetiLanzado = true;
                        confetti({ particleCount: 120, spread: 80, origin: { y: 0.6 } });
                    }
                } else {
                    seccionPodio.classList.add('hidden');
                    confetiLanzado = false;
                }

                // 3. Actualizar Cabecera
                const badge = document.getElementById('badge-estado');
                badge.textContent = estado === 'DESEMPATE' ? '🔥 MUERTE SÚBITA' : estado.replace('_', ' ');
                badge.className = estado === 'DESEMPATE'
                    ? 'font-orbitron px-3 py-1 text-[11px] font-bold uppercase rounded bg-rose-600 text-white animate-pulse'
                    : 'font-orbitron px-3 py-1 text-[11px] font-bold uppercase rounded bg-cyan-500/20 text-[#00E5FF] border border-[#00E5FF]';

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
                    const etiquetaFase = eq.en_desempate ? '🔥 DESEMPATE' : (eq.finalizo_carrera ? '🏁 META' : `ETAPA ${eq.fase_actual}`);
                    const leftPos = Math.min(92, Math.max(2, eq.porcentaje_pista));

                    return `
                    <div class="bg-[#07101E]/90 border ${esLider ? 'border-amber-400' : 'border-slate-800'} rounded-lg p-3 relative overflow-hidden">
                        <div class="flex justify-between items-center mb-2 z-10 relative">
                            <div class="flex items-center gap-2">
                                <span class="font-orbitron font-bold text-xs px-2 py-0.5 rounded bg-slate-800 text-[#00E5FF]">#${eq.posicion}</span>
                                <span class="font-bold text-lg text-white tracking-wide">${eq.nombre_equipo}</span>
                                ${esLider ? '<span class="font-orbitron text-amber-400 font-bold text-[10px]">👑 LÍDER</span>' : ''}
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="font-orbitron text-[10px] px-2 py-0.5 rounded bg-slate-800 text-[#00E5FF]">${etiquetaFase} (${eq.preguntas_contestadas}/${eq.total_preguntas})</span>
                                <span class="font-bold text-emerald-400 text-base">${eq.puntaje_total} PTS</span>
                            </div>
                        </div>

                        <div class="relative h-10 bg-[#0F2342] rounded-lg border border-slate-700/70 flex items-center overflow-hidden">
                            <div class="absolute inset-0 grid grid-cols-4 pointer-events-none">
                                <div class="border-r border-dashed border-slate-600/50"></div>
                                <div class="border-r border-dashed border-slate-600/50"></div>
                                <div class="border-r border-dashed border-slate-600/50"></div>
                                <div class="bg-gradient-to-r from-transparent to-emerald-500/10"></div>
                            </div>

                            <div class="h-2 rounded-full transition-all duration-700 ml-2"
                                 style="width: ${leftPos}%; background: ${eq.color_carril}; box-shadow: 0 0 10px ${eq.color_carril};">
                            </div>

                            <div class="absolute top-1 robot-transition flex items-center justify-center w-8 h-8 rounded-full border-2 bg-[#07101E] ${esLider ? 'leader-pulse' : ''}"
                                 style="left: calc(${leftPos}% - 12px); border-color: ${eq.color_carril}; box-shadow: 0 0 12px ${eq.color_carril};">
                                <img src="/favicon.svg" class="w-6 h-6 rounded-full" alt="Bot">
                            </div>
                        </div>
                    </div>`;
                }).join('');

                tabla.innerHTML = data.equipos.map((eq, idx) => {
                    const medalla = idx === 0 ? '🥇' : (idx === 1 ? '🥈' : (idx === 2 ? '🥉' : `#${eq.posicion}`));
                    return `
                    <div class="flex items-center justify-between p-3 rounded-lg bg-[#07101E]/80 border ${eq.en_desempate ? 'border-rose-500' : 'border-slate-800'}">
                        <div class="flex items-center gap-3">
                            <span class="font-orbitron text-base font-bold w-7 text-center text-[#00E5FF]">${medalla}</span>
                            <div>
                                <p class="font-bold text-base text-white leading-tight">${eq.nombre_equipo}</p>
                                <p class="font-orbitron text-[10px] text-slate-400">✅ ${eq.aciertos_totales} ACIERTOS • ⏱️ ${eq.tiempo_segundos}S</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xl font-bold text-[#00E5FF]">${eq.puntaje_total}</span>
                            <span class="font-orbitron block text-[9px] text-slate-400 uppercase">PUNTOS</span>
                        </div>
                    </div>`;
                }).join('');

            } catch (e) {
                console.error('Error sincronizando carrera:', e);
            }
        }

        actualizarCarrera();
        setInterval(actualizarCarrera, 1500);
    </script>
</body>
</html>