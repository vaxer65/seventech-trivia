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
        background-color: #07101E;
        color: #FFFFFF;
        font-family: var(--font-main);
        font-weight: 500;
        letter-spacing: 0.02em;
    }

    h1, h2, h3, button, input, select, textarea {
        font-family: var(--font-main);
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

    /* ESTILO DE TÍTULO CYBERPUNK 2077 + PARPADEO LENTO NEÓN */
    .cyber-title {
        font-family: 'Orbitron', 'Rajdhani', sans-serif !important;
        font-weight: 900;
        text-transform: uppercase;
        font-style: italic;
        letter-spacing: 0.08em;
        color: #00E5FF;
        display: inline-block;
        transform: skewX(-12deg);
        text-shadow: 
            3px 3px 0px #0F2342,
            -1px -1px 0px #FFFFFF,
            0 0 18px rgba(0, 229, 255, 0.85),
            0 0 35px rgba(0, 229, 255, 0.4);
        animation: cyber-slow-flicker 4.5s infinite ease-in-out;
    }

    @keyframes cyber-slow-flicker {
        0%, 100% {
            opacity: 1;
            text-shadow: 3px 3px 0px #0F2342, -1px -1px 0px #FFFFFF, 0 0 20px rgba(0, 229, 255, 0.9), 0 0 40px rgba(0, 229, 255, 0.5);
        }
        45% {
            opacity: 0.92;
        }
        50% {
            opacity: 0.45;
            text-shadow: 2px 2px 0px #0F2342, 0 0 6px rgba(0, 229, 255, 0.3);
        }
        55% {
            opacity: 0.95;
        }
        75% {
            opacity: 0.75;
            text-shadow: 3px 3px 0px #0F2342, -1px -1px 0px #00E5FF, 0 0 12px rgba(0, 229, 255, 0.6);
        }
    }

    .circuit-bg {
        background-image: radial-gradient(rgba(0, 229, 255, 0.25) 1px, transparent 1px),
                          linear-gradient(to bottom, #07101E, #09172B);
        background-size: 36px 36px, 100% 100%;
    }

    .neon-card, .neon-border, .card {
        background-color: rgba(15, 35, 66, 0.92);
        border: 1px solid rgba(0, 229, 255, 0.4);
        box-shadow: 0 0 20px rgba(0, 229, 255, 0.15), inset 0 0 12px rgba(0, 229, 255, 0.05);
        clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 14px, 100% 100%, 14px 100%, 0 calc(100% - 14px));
    }

    .robot-transition { transition: left 0.8s cubic-bezier(0.22, 1, 0.36, 1); }
    @keyframes pulse-fast { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.08); } }
    .leader-pulse { animation: pulse-fast 1.5s infinite; }
</style>
