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
        background-color: #c4cedb;
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
        background-color: #c4cedb;
        background-image:
            radial-gradient(rgba(15, 23, 42, 0.10) 1px, transparent 1px),
            linear-gradient(135deg, #dce3ec 0%, #c4cedb 50%, #b3bfce 100%);
        background-size: 36px 36px, 100% 100%;
        background-attachment: fixed;
    }

    .neon-card, .neon-border, .card {
        background-color: rgba(15, 23, 42, 0.94);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
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
        right: 1rem;
        bottom: var(--toast-bottom, 1rem);
        margin: 0;
        max-width: min(24rem, calc(100vw - 2rem));
        width: max-content;
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
