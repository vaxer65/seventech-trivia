<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seven Tech - Portal Principal de Trivia</title>
    @include('partials.cyberpunk-head')
</head>
<body class="circuit-bg min-h-screen flex flex-col justify-between p-6">

    <main class="max-w-5xl w-full mx-auto my-auto text-center space-y-10">
        
        <!-- LOGO + TÍTULO CYBERPUNK CON PARPADEO LENTO -->
        <div class="space-y-4">
            <div class="inline-flex items-center justify-center p-3 rounded-2xl bg-[#0F2342] border-2 border-[#00E5FF] shadow-[0_0_30px_rgba(0,229,255,0.35)]">
                <img src="/favicon.svg" alt="Seven Tech Logo" class="w-20 h-20">
            </div>
            <div>
                <p class="font-orbitron text-xs uppercase text-[#00E5FF] tracking-[0.25em] mb-2">// SISTEMA DE COMPETENCIA EN TIEMPO REAL //</p>
                <h1 class="cyber-title text-5xl sm:text-6xl md:text-7xl">SEVEN TECH</h1>
                <p class="text-slate-300 text-lg mt-3 max-w-xl mx-auto">Selecciona tu modo de acceso para ingresar a la plataforma oficial de trivia tecnológica.</p>
            </div>
        </div>

        <!-- TARJETAS DE SELECCIÓN DE ROL -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-left">

            <!-- 1. CONCURSANTE -->
            <a href="{{ route('concursante.index') }}" class="neon-card p-6 rounded-2xl hover:border-[#00E5FF] hover:-translate-y-1.5 transition duration-300 flex flex-col justify-between group">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-4xl">🎮</span>
                        <span class="font-orbitron text-[10px] px-2.5 py-1 rounded bg-[#00E5FF]/20 text-[#00E5FF] border border-[#00E5FF]/50">EQUIPOS</span>
                    </div>
                    <h2 class="text-2xl font-bold text-white group-hover:text-[#00E5FF] transition">MODO CONCURSANTE</h2>
                    <p class="text-slate-300 text-sm mt-2">Registra a tu equipo (de 2 a 5 integrantes), responde las preguntas contra reloj y avanza por las etapas de la carrera.</p>
                </div>
                <div class="mt-6 pt-3 border-t border-slate-800 flex items-center justify-between text-[#00E5FF] font-orbitron text-xs font-bold">
                    <span>ENTRAR A PISTA</span>
                    <span class="group-hover:translate-x-1 transition">→</span>
                </div>
            </a>

            <!-- 2. ESPECTADOR -->
            <a href="{{ route('espectador.index') }}" class="neon-card p-6 rounded-2xl hover:border-emerald-400 hover:-translate-y-1.5 transition duration-300 flex flex-col justify-between group">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-4xl">🖥️</span>
                        <span class="font-orbitron text-[10px] px-2.5 py-1 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-400/50">PANTALLA GIGANTE</span>
                    </div>
                    <h2 class="text-2xl font-bold text-white group-hover:text-emerald-400 transition">MODO ESPECTADOR</h2>
                    <p class="text-slate-300 text-sm mt-2">Visualiza en vivo la pista de carreras 2D, el código QR de acceso y la tabla de posiciones sin revelar las preguntas.</p>
                </div>
                <div class="mt-6 pt-3 border-t border-slate-800 flex items-center justify-between text-emerald-400 font-orbitron text-xs font-bold">
                    <span>VER CARRERA EN VIVO</span>
                    <span class="group-hover:translate-x-1 transition">→</span>
                </div>
            </a>

            <!-- 3. ADMINISTRADOR -->
            <a href="{{ route('admin.index') }}" class="neon-card p-6 rounded-2xl hover:border-amber-400 hover:-translate-y-1.5 transition duration-300 flex flex-col justify-between group">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-4xl">🔐</span>
                        <span class="font-orbitron text-[10px] px-2.5 py-1 rounded bg-amber-500/20 text-amber-300 border border-amber-400/50">CONTROL TOTAL</span>
                    </div>
                    <h2 class="text-2xl font-bold text-white group-hover:text-amber-400 transition">ADMINISTRADOR</h2>
                    <p class="text-slate-300 text-sm mt-2">Centro de mando protegido con clave para iniciar la carrera, gestionar etapas, módulos y activar la Muerte Súbita.</p>
                </div>
                <div class="mt-6 pt-3 border-t border-slate-800 flex items-center justify-between text-amber-400 font-orbitron text-xs font-bold">
                    <span>PANEL DE CONTROL</span>
                    <span class="group-hover:translate-x-1 transition">→</span>
                </div>
            </a>

        </div>
            <!-- SALÓN DE LA FAMA PÚBLICO (SIN NOMBRES PRIVADOS DE INTEGRANTES) -->
        @if(isset($historial) && $historial->count() > 0)
            <div class="pt-6 text-left">
                <p class="font-orbitron text-xs uppercase text-[#00E5FF] tracking-[0.2em] mb-3 text-center">// SALÓN DE LA FAMA • EDICIONES ANTERIORES //</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($historial as $h)
                        <div class="neon-card p-4 rounded-xl">
                            <div class="flex justify-between items-center border-b border-slate-800 pb-2 mb-2">
                                <h3 class="font-bold text-lg text-white">{{ $h->nombre_edicion }}</h3>
                                <span class="font-orbitron text-[10px] text-slate-400">{{ $h->created_at->format('d/m/Y') }}</span>
                            </div>
                            <div class="space-y-1 text-xs">
                                @foreach($h->podio_top3 ?? [] as $g)
                                    <div class="flex justify-between items-center py-1 px-2 rounded bg-[#07101E]/70">
                                        <span class="font-bold text-white">
                                            {{ $g['posicion'] == 1 ? '🥇' : ($g['posicion'] == 2 ? '🥈' : '🥉') }} {{ $g['nombre_equipo'] }}
                                        </span>
                                        <span class="font-orbitron text-[#00E5FF]">{{ $g['puntaje_total'] }} PTS • {{ $g['tiempo_segundos'] }}s</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </main>

    <footer class="text-center text-xs text-slate-500 py-4">
        SEVEN TECH COMMUNITY • AUDITORIO CENI UEB 2026
    </footer>

</body>
</html>
