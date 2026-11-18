<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>

<style>
    :root {
        --font-main: 'Rajdhani', sans-serif;
        --font-secondary: 'Orbitron', sans-serif;
    }

    body {
        background-color: #04060d;
        color: #f8fafc;
        font-family: var(--font-main);
        font-weight: 500;
        letter-spacing: 0.02em;
    }

    h1, h2, h3, button, input, select, textarea {
        font-family: var(--font-main);
    }

    input, select, textarea {
        color-scheme: dark;
    }

    input:-webkit-autofill,
    input:-webkit-autofill:hover,
    input:-webkit-autofill:focus {
        -webkit-text-fill-color: #f8fafc;
        -webkit-box-shadow: 0 0 0px 1000px #0f172a inset;
        transition: background-color 5000s ease-in-out 0s;
    }

    input:user-invalid,
    select:user-invalid,
    textarea:user-invalid {
        border-color: rgba(244, 63, 94, 0.85) !important;
        box-shadow: 0 0 0 2px rgba(244, 63, 94, 0.2);
    }

    .font-orbitron,
    .hud-subtext,
    footer,
    #badge-estado,
    #badge-nivel,
    #progreso-fase,
    #segundos-restantes {
        font-family: var(--font-secondary) !important;
        letter-spacing: 0.06em;
    }

    /* TÍTULO CYBERPUNK LEGIBLE SOBRE PLATEADO Y PIZARRA OSCURA */
    .cyber-title {
        font-family: 'Orbitron', 'Rajdhani', sans-serif !important;
        font-weight: 900;
        text-transform: uppercase;
        font-style: italic;
        letter-spacing: 0.08em;
        color: #10b981;
        display: inline-block;
        transform: skewX(-10deg);
        text-shadow:
            2px 2px 0px #0f172a,
            -1px -1px 0px #0f172a,
            1px -1px 0px #0f172a,
            -1px 1px 0px #0f172a,
            0 0 18px rgba(16, 185, 129, 0.55),
            0 0 32px rgba(0, 229, 255, 0.25);
        animation: cyber-slow-flicker 5s infinite ease-in-out;
    }

    @keyframes cyber-slow-flicker {
        0%, 100% {
            opacity: 1;
            text-shadow:
                2px 2px 0px #0f172a,
                -1px -1px 0px #0f172a,
                1px -1px 0px #0f172a,
                -1px 1px 0px #0f172a,
                0 0 20px rgba(16, 185, 129, 0.65),
                0 0 36px rgba(52, 211, 153, 0.35);
        }
        50% {
            opacity: 0.9;
            text-shadow:
                2px 2px 0px #0f172a,
                -1px -1px 0px #0f172a,
                1px -1px 0px #0f172a,
                -1px 1px 0px #0f172a,
                0 0 12px rgba(16, 185, 129, 0.35);
        }
    }

    @keyframes brilloEsmeraldaSuave {
        0%, 100% {
            border-color: rgba(16, 185, 129, 0.38);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.24),
                        0 0 16px rgba(16, 185, 129, 0.12),
                        inset 0 0 14px rgba(16, 185, 129, 0.05);
        }
        50% {
            border-color: rgba(16, 185, 129, 0.72);
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.32),
                        0 0 26px rgba(16, 185, 129, 0.28),
                        inset 0 0 18px rgba(16, 185, 129, 0.11);
        }
    }

    @keyframes llamaAnimada {
        0%, 100% {
            transform: translateY(0) scale(1) rotate(-3deg);
            filter: drop-shadow(0 0 6px rgba(251, 146, 60, 0.75));
        }
        50% {
            transform: translateY(-2px) scale(1.14) rotate(3deg);
            filter: drop-shadow(0 0 12px rgba(249, 115, 22, 0.95));
        }
    }

    .llama-animada {
        display: inline-block;
        animation: llamaAnimada 1.15s ease-in-out infinite;
    }

    .circuit-bg {
        background-color: #04060d;
        background-image:
            radial-gradient(circle at 50% 20%, #0d1629 0%, #060913 70%, #04060d 100%);
        background-size: 100% 100%;
        background-attachment: fixed;
    }

    #canvas-fondo-interactivo {
        position: fixed;
        inset: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
        pointer-events: none;
    }

    .neon-card, .neon-border, .card {
        background-color: rgba(15, 23, 42, 0.94);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border: 1px solid rgba(16, 185, 129, 0.45);
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.24),
                    0 0 18px rgba(16, 185, 129, 0.14),
                    inset 0 0 14px rgba(16, 185, 129, 0.06);
        animation: brilloEsmeraldaSuave 5.5s ease-in-out infinite;
        clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 14px, 100% 100%, 14px 100%, 0 calc(100% - 14px));
    }

    .podium-lane-1 {
        border-color: rgba(250, 204, 21, 0.85) !important;
        box-shadow: 0 0 22px rgba(250, 204, 21, 0.28), inset 0 0 14px rgba(250, 204, 21, 0.08);
    }
    .podium-lane-2 {
        border-color: rgba(226, 232, 240, 0.8) !important;
        box-shadow: 0 0 18px rgba(226, 232, 240, 0.22), inset 0 0 12px rgba(226, 232, 240, 0.06);
    }
    .podium-lane-3 {
        border-color: rgba(217, 119, 6, 0.8) !important;
        box-shadow: 0 0 18px rgba(217, 119, 6, 0.24), inset 0 0 12px rgba(217, 119, 6, 0.06);
    }

    dialog.ui-dialog {
        padding: 0;
        border: 1px solid rgba(16, 185, 129, 0.55);
        border-radius: 1rem;
        background: rgba(15, 23, 42, 0.96);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        color: #f8fafc;
        max-width: 28rem;
        width: calc(100% - 2rem);
        margin: auto;
        box-shadow: 0 20px 50px rgba(2, 6, 23, 0.65), 0 0 28px rgba(16, 185, 129, 0.24);
        opacity: 0;
        transform: translateY(8px) scale(0.98);
        transition: opacity 0.2s ease, transform 0.2s ease, display 0.2s, overlay 0.2s;
        transition-behavior: allow-discrete;
    }

    dialog.ui-dialog[open] {
        display: block;
        opacity: 1;
        transform: translateY(0) scale(1);
    }

    dialog.ui-dialog:modal {
        overscroll-behavior: contain;
    }

    dialog.ui-dialog::backdrop {
        background: rgba(2, 6, 23, 0.72);
        backdrop-filter: blur(6px);
        overflow: hidden;
        overscroll-behavior: contain;
    }

    .ui-toast[popover] {
        position: fixed;
        inset: auto;
        right: max(1rem, env(safe-area-inset-right));
        bottom: calc(var(--toast-bottom, 1rem) + env(safe-area-inset-bottom));
        margin: 0;
        max-width: min(24rem, calc(100vw - 2rem));
        width: max-content;
        max-height: calc((100vh - 4rem - env(safe-area-inset-bottom) - env(safe-area-inset-top)) / 3);
        max-height: calc((100dvh - 4rem - env(safe-area-inset-bottom) - env(safe-area-inset-top)) / 3);
        overflow-y: auto;
        align-items: flex-start;
        overflow-wrap: anywhere;
        border-radius: 0.9rem;
        border: 1px solid rgba(16, 185, 129, 0.55);
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        color: #f8fafc;
        box-shadow: 0 12px 30px rgba(2, 6, 23, 0.45), 0 0 20px rgba(16, 185, 129, 0.22);
        pointer-events: auto;
        opacity: 0;
        transform: translateY(10px);
        transition: opacity 0.22s ease, transform 0.22s ease, display 0.22s, overlay 0.22s;
        transition-behavior: allow-discrete;
    }

    .ui-toast[popover]:is(:popover-open, .\:popover-open) {
        display: flex;
        opacity: 1;
        transform: translateY(0);
    }

    .robot-transition { transition: left 0.8s cubic-bezier(0.22, 1, 0.36, 1); }
    @keyframes pulse-fast { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.08); } }
    .leader-pulse { animation: pulse-fast 1.5s infinite; }

    @media (prefers-reduced-motion: reduce) {
        .neon-card, .neon-border, .card,
        .cyber-title, .llama-animada, .leader-pulse,
        dialog.ui-dialog, .ui-toast[popover] {
            animation: none !important;
            transition: none !important;
        }
    }
</style>

<script>
    (function () {
        if (window.__fondoInteractivoInit) return;
        window.__fondoInteractivoInit = true;

        function iniciarFondoInteractivo() {
            let canvas = document.getElementById('canvas-fondo-interactivo');
            if (!canvas) {
                canvas = document.createElement('canvas');
                canvas.id = 'canvas-fondo-interactivo';
                canvas.setAttribute('aria-hidden', 'true');
                document.body.prepend(canvas);
            }

            const ctx = canvas.getContext('2d');
            if (!ctx) return;

            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
            const pointer = { x: 0, y: 0, active: false };
            const pointerRadius = 160;
            const paletas = [
                '16, 185, 129',  // Esmeralda (#10b981)
                '0, 229, 255',   // Cian (#00e5ff)
                '192, 132, 252', // Violeta (#c084fc)
            ];

            let width = 0;
            let height = 0;
            let dpr = 1;
            let maxLinkDist = 145;
            let nodos = [];
            let particulas = [];
            let rafId = null;
            let tiempo = 0;

            function crearEscena() {
                width = window.innerWidth;
                height = window.innerHeight;
                dpr = Math.min(window.devicePixelRatio || 1, 1.5);

                canvas.width = Math.floor(width * dpr);
                canvas.height = Math.floor(height * dpr);
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

                const esMovil = width < 768;
                const cantidadNodos = esMovil ? 24 : 55;
                const cantidadParticulas = esMovil ? 18 : 35;
                maxLinkDist = esMovil ? 115 : 145;

                nodos = Array.from({ length: cantidadNodos }, (_, idx) => ({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    vx: (Math.random() - 0.5) * 0.36,
                    vy: (Math.random() - 0.5) * 0.36,
                    r: 1.3 + Math.random() * 1.4,
                    rgb: idx % 3 === 0 ? paletas[1] : paletas[0],
                }));

                particulas = Array.from({ length: cantidadParticulas }, (_, idx) => ({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    vx: (Math.random() - 0.5) * 0.28,
                    vy: -0.14 - Math.random() * 0.25,
                    r: 1.5 + Math.random() * 2.2,
                    rgb: paletas[idx % paletas.length],
                    alpha: 0.28 + Math.random() * 0.38,
                    fase: Math.random() * Math.PI * 2,
                }));
            }

            function dibujarFrame(animar) {
                ctx.clearRect(0, 0, width, height);
                tiempo += 0.014;

                // Capa 1: Partículas ambientales luminosas en flujo suave (halo dual ligero sin filtro costoso)
                for (let i = 0; i < particulas.length; i++) {
                    const p = particulas[i];
                    if (animar) {
                        p.x += p.vx + Math.sin(tiempo + p.fase) * 0.14;
                        p.y += p.vy;
                        if (p.y < -12) { p.y = height + 12; p.x = Math.random() * width; }
                        if (p.x < -12) p.x = width + 12;
                        if (p.x > width + 12) p.x = -12;
                    }

                    ctx.beginPath();
                    ctx.moveTo(p.x - p.vx * 10, p.y - p.vy * 10);
                    ctx.lineTo(p.x, p.y);
                    ctx.strokeStyle = `rgba(${p.rgb}, ${(p.alpha * 0.42).toFixed(3)})`;
                    ctx.lineWidth = p.r * 0.85;
                    ctx.stroke();

                    ctx.fillStyle = `rgba(${p.rgb}, ${(p.alpha * 0.22).toFixed(3)})`;
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.r * 2.1, 0, Math.PI * 2);
                    ctx.fill();

                    ctx.fillStyle = `rgba(${p.rgb}, ${p.alpha})`;
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                    ctx.fill();
                }

                // Capa 2: Constelación geométrica reactiva al puntero
                for (let i = 0; i < nodos.length; i++) {
                    const n = nodos[i];
                    if (animar) {
                        n.x += n.vx;
                        n.y += n.vy;
                        if (n.x < 0 || n.x > width) n.vx *= -1;
                        if (n.y < 0 || n.y > height) n.vy *= -1;
                    }
                }

                const maxDistSq = maxLinkDist * maxLinkDist;
                const pointerDistSqMax = pointerRadius * pointerRadius;

                for (let i = 0; i < nodos.length; i++) {
                    const a = nodos[i];
                    for (let j = i + 1; j < nodos.length; j++) {
                        const b = nodos[j];
                        const dx = a.x - b.x;
                        const dy = a.y - b.y;
                        const d2 = dx * dx + dy * dy;
                        if (d2 < maxDistSq) {
                            const alpha = (1 - Math.sqrt(d2) / maxLinkDist) * 0.22;
                            ctx.strokeStyle = `rgba(16, 185, 129, ${alpha.toFixed(3)})`;
                            ctx.lineWidth = 0.85;
                            ctx.beginPath();
                            ctx.moveTo(a.x, a.y);
                            ctx.lineTo(b.x, b.y);
                            ctx.stroke();
                        }
                    }

                    let cercaCursor = false;
                    if (pointer.active) {
                        const pdx = a.x - pointer.x;
                        const pdy = a.y - pointer.y;
                        const pd2 = pdx * pdx + pdy * pdy;
                        if (pd2 < pointerDistSqMax) {
                            cercaCursor = true;
                            const factor = 1 - Math.sqrt(pd2) / pointerRadius;
                            ctx.strokeStyle = `rgba(0, 229, 255, ${(factor * 0.42).toFixed(3)})`;
                            ctx.lineWidth = 1.05;
                            ctx.beginPath();
                            ctx.moveTo(a.x, a.y);
                            ctx.lineTo(pointer.x, pointer.y);
                            ctx.stroke();
                        }
                    }

                    ctx.fillStyle = cercaCursor ? 'rgba(0, 229, 255, 0.82)' : `rgba(${a.rgb}, 0.52)`;
                    ctx.beginPath();
                    ctx.arc(a.x, a.y, cercaCursor ? a.r + 0.9 : a.r, 0, Math.PI * 2);
                    ctx.fill();
                }
            }

            const intervaloFrameMs = 28; // ~35 FPS para bajo consumo de CPU/GPU
            let ultimoFrameTs = 0;

            function bucle(ts) {
                if (document.hidden || reducedMotion.matches) {
                    rafId = null;
                    return;
                }
                if (!ultimoFrameTs || (ts - ultimoFrameTs) >= intervaloFrameMs) {
                    ultimoFrameTs = ts || performance.now();
                    dibujarFrame(true);
                }
                rafId = window.requestAnimationFrame(bucle);
            }

            function iniciarBucle() {
                if (rafId !== null) return;
                if (reducedMotion.matches) {
                    dibujarFrame(false);
                    return;
                }
                if (!document.hidden) {
                    ultimoFrameTs = 0;
                    rafId = window.requestAnimationFrame(bucle);
                }
            }

            function detenerBucle() {
                if (rafId !== null) {
                    window.cancelAnimationFrame(rafId);
                    rafId = null;
                }
            }

            crearEscena();
            iniciarBucle();

            window.addEventListener('mousemove', (e) => {
                pointer.x = e.clientX;
                pointer.y = e.clientY;
                pointer.active = true;
            }, { passive: true });

            window.addEventListener('mouseleave', () => {
                pointer.active = false;
            }, { passive: true });

            let resizeTimer = null;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    crearEscena();
                    if (reducedMotion.matches) {
                        dibujarFrame(false);
                    }
                }, 150);
            }, { passive: true });

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    detenerBucle();
                } else {
                    iniciarBucle();
                }
            });

            if (typeof reducedMotion.addEventListener === 'function') {
                reducedMotion.addEventListener('change', () => {
                    detenerBucle();
                    iniciarBucle();
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', iniciarFondoInteractivo);
        } else {
            iniciarFondoInteractivo();
        }
    })();
</script>
