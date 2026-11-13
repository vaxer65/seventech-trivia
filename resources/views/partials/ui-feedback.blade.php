{{-- Modal de confirmación/aviso nativo (<dialog>) y toasts accesibles (popover="manual") --}}
<dialog id="modal-confirmacion-ui"
        class="ui-dialog"
        closedby="any"
        aria-labelledby="modal-confirmacion-titulo"
        aria-describedby="modal-confirmacion-mensaje">
    <form method="dialog" class="p-5 sm:p-6 space-y-4">
        <div class="flex items-start gap-3">
            <div id="modal-confirmacion-icono" class="w-10 h-10 rounded-xl bg-emerald-500/15 border border-emerald-400/50 text-emerald-300 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
            </div>
            <div class="space-y-1 min-w-0">
                <h2 id="modal-confirmacion-titulo" class="font-orbitron text-sm sm:text-base font-bold text-white uppercase tracking-wide">
                    Confirmar acción
                </h2>
                <p id="modal-confirmacion-mensaje" class="text-sm text-slate-300 leading-relaxed"></p>
            </div>
        </div>
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5 pt-2 border-t border-slate-800/90">
            <button type="submit"
                    id="modal-confirmacion-cancelar"
                    value="cancel"
                    autofocus
                    class="min-h-[48px] px-4 py-2.5 rounded-xl bg-slate-800/90 hover:bg-slate-700 border border-slate-600/70 text-slate-200 font-orbitron text-xs font-bold uppercase transition">
                Cancelar
            </button>
            <button type="submit"
                    id="modal-confirmacion-aceptar"
                    value="confirm"
                    class="min-h-[48px] px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-orbitron text-xs font-bold uppercase transition">
                Confirmar
            </button>
        </div>
    </form>
</dialog>

<div id="toast-region"
     role="region"
     aria-label="Notificaciones"
     class="fixed bottom-4 right-4 z-50 flex flex-col gap-2.5 pointer-events-none">
</div>
<div id="toast-aria-fallback" class="sr-only" aria-live="polite" aria-atomic="true"></div>

<script>
    (function () {
        if (window.__uiFeedbackInitialized) return;
        window.__uiFeedbackInitialized = true;

        const dialog = document.getElementById('modal-confirmacion-ui');
        const elTitulo = document.getElementById('modal-confirmacion-titulo');
        const elMensaje = document.getElementById('modal-confirmacion-mensaje');
        const elIcono = document.getElementById('modal-confirmacion-icono');
        const btnCancelar = document.getElementById('modal-confirmacion-cancelar');
        const btnAceptar = document.getElementById('modal-confirmacion-aceptar');
        const toastRegion = document.getElementById('toast-region');
        const toastAriaFallback = document.getElementById('toast-aria-fallback');
        const colaToastsDiferidos = [];
        let contadorToasts = 0;
        let resolverConfirmacionActual = null;
        let elementoPrevioFoco = null;

        if (dialog && !('closedBy' in HTMLDialogElement.prototype)) {
            let presionFuera = false;
            const estaFuera = (e) => {
                const r = dialog.getBoundingClientRect();
                return e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom;
            };
            dialog.addEventListener('pointerdown', (e) => { presionFuera = estaFuera(e); });
            dialog.addEventListener('pointerup', (e) => {
                if (presionFuera && estaFuera(e) && dialog.open) dialog.close('cancel');
                presionFuera = false;
            });
        }

        function drenarColaToasts() {
            while (colaToastsDiferidos.length > 0 && (!dialog || !dialog.open)) {
                const siguiente = colaToastsDiferidos.shift();
                window.mostrarToast(siguiente.mensaje, siguiente.tipo);
            }
        }

        if (dialog) {
            dialog.addEventListener('close', () => {
                const confirmado = dialog.returnValue === 'confirm';
                dialog.returnValue = '';
                const resolver = resolverConfirmacionActual;
                resolverConfirmacionActual = null;
                if (elementoPrevioFoco && typeof elementoPrevioFoco.focus === 'function') {
                    elementoPrevioFoco.focus();
                }
                elementoPrevioFoco = null;
                if (resolver) resolver(confirmado);
                drenarColaToasts();
            });
        }

        window.mostrarConfirmacion = function (opciones = {}) {
            if (!dialog || typeof dialog.showModal !== 'function') {
                return Promise.resolve(false);
            }
            if (dialog.open && resolverConfirmacionActual) {
                resolverConfirmacionActual(false);
                resolverConfirmacionActual = null;
                dialog.close('cancel');
            }

            const {
                titulo = 'Confirmar acción',
                mensaje = '',
                textoConfirmar = 'Confirmar',
                textoCancelar = 'Cancelar',
                variante = 'emerald',
                soloAviso = false,
            } = opciones;

            elTitulo.textContent = titulo;
            elMensaje.textContent = mensaje;
            btnCancelar.textContent = textoCancelar;
            btnCancelar.classList.toggle('hidden', !!soloAviso);
            btnAceptar.textContent = textoConfirmar;

            if (variante === 'danger') {
                elIcono.className = 'w-10 h-10 rounded-xl bg-rose-500/20 border border-rose-400/60 text-rose-300 flex items-center justify-center shrink-0';
                btnAceptar.className = 'min-h-[48px] px-5 py-2.5 rounded-xl bg-rose-500 hover:bg-rose-400 text-white font-orbitron text-xs font-bold uppercase transition';
            } else {
                elIcono.className = 'w-10 h-10 rounded-xl bg-emerald-500/15 border border-emerald-400/50 text-emerald-300 flex items-center justify-center shrink-0';
                btnAceptar.className = 'min-h-[48px] px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-orbitron text-xs font-bold uppercase transition';
            }

            elementoPrevioFoco = document.activeElement;
            dialog.returnValue = '';

            return new Promise((resolve) => {
                resolverConfirmacionActual = resolve;
                dialog.showModal();
                if (soloAviso) {
                    btnAceptar.focus();
                } else {
                    btnCancelar.focus();
                }
            });
        };

        function reposicionarToasts() {
            if (!toastRegion) return;
            const activos = Array.from(toastRegion.querySelectorAll('.ui-toast')).reverse();
            let offsetRem = 1;
            activos.forEach((toast) => {
                toast.style.setProperty('--toast-bottom', `${offsetRem}rem`);
                offsetRem += 4.35;
            });
        }

        function ocultarToastElemento(toast, triggerControl) {
            if (!toast || toast.dataset.cerrando === '1') return;
            toast.dataset.cerrando = '1';
            const estabaEnfocado = toast.contains(document.activeElement);
            try {
                if (typeof toast.hidePopover === 'function') {
                    toast.hidePopover();
                } else {
                    toast.classList.remove(':popover-open');
                }
            } catch (e) {}

            const siguiente = toast.nextElementSibling || toast.previousElementSibling;
            toast.remove();
            reposicionarToasts();

            if (estabaEnfocado) {
                const btnSiguiente = siguiente ? siguiente.querySelector('button') : null;
                if (btnSiguiente) {
                    btnSiguiente.focus();
                } else if (triggerControl && typeof triggerControl.focus === 'function') {
                    triggerControl.focus();
                }
            }
        }

        window.mostrarToast = function (mensaje, tipo = 'success') {
            if (dialog && dialog.open) {
                colaToastsDiferidos.push({ mensaje, tipo });
                return;
            }
            if (!toastRegion) return;

            const esError = tipo === 'error';
            const textoLimpio = String(mensaje || '').trim();
            if (!textoLimpio) return;

            const controlOrigen = document.activeElement;
            const toastId = `ui-toast-${++contadorToasts}`;
            const toast = document.createElement('div');
            toast.id = toastId;
            toast.setAttribute('popover', 'manual');
            toast.className = `ui-toast items-center justify-between gap-3 px-4 py-3 text-sm font-semibold ${
                esError ? 'border-rose-400/75 text-rose-100' : 'border-emerald-400/70 text-emerald-50'
            }`;

            const cuerpo = document.createElement('div');
            cuerpo.className = 'flex items-center gap-2.5 min-w-0';

            const icono = document.createElement('span');
            icono.setAttribute('aria-hidden', 'true');
            icono.className = esError ? 'text-rose-400 shrink-0' : 'text-emerald-400 shrink-0';
            icono.textContent = esError ? '⚠️' : '✅';

            const spanTexto = document.createElement('span');
            spanTexto.className = 'leading-snug break-words';
            spanTexto.textContent = textoLimpio;

            cuerpo.appendChild(icono);
            cuerpo.appendChild(spanTexto);

            const btnCerrar = document.createElement('button');
            btnCerrar.type = 'button';
            btnCerrar.setAttribute('popovertarget', toastId);
            btnCerrar.setAttribute('popovertargetaction', 'hide');
            btnCerrar.setAttribute('aria-label', 'Cerrar notificación');
            btnCerrar.className = 'min-w-[36px] min-h-[36px] rounded-lg hover:bg-slate-800 text-slate-300 hover:text-white flex items-center justify-center shrink-0 transition';
            btnCerrar.textContent = '✕';
            btnCerrar.addEventListener('click', (e) => {
                e.preventDefault();
                ocultarToastElemento(toast, controlOrigen);
            });

            toast.appendChild(cuerpo);
            toast.appendChild(btnCerrar);
            toastRegion.appendChild(toast);

            const soportaPopover = Object.hasOwn(HTMLElement.prototype, 'popover') && typeof toast.showPopover === 'function';
            if (soportaPopover) {
                try {
                    toast.showPopover();
                } catch (e) {
                    toast.classList.add(':popover-open');
                }
            } else {
                toast.classList.add(':popover-open');
            }
            reposicionarToasts();

            if (typeof document.ariaNotify === 'function') {
                document.ariaNotify(textoLimpio);
            } else if (toastAriaFallback) {
                toastAriaFallback.textContent = '';
                requestAnimationFrame(() => { toastAriaFallback.textContent = textoLimpio; });
            }

            if (!esError) {
                let timerToast = null;
                const programarCierre = () => {
                    clearTimeout(timerToast);
                    timerToast = setTimeout(() => {
                        if (!toast.matches(':focus-within') && !toast.matches(':hover')) {
                            ocultarToastElemento(toast, controlOrigen);
                        }
                    }, 4800);
                };
                toast.addEventListener('mouseenter', () => clearTimeout(timerToast));
                toast.addEventListener('mouseleave', programarCierre);
                toast.addEventListener('focusin', () => clearTimeout(timerToast));
                toast.addEventListener('focusout', programarCierre);
                programarCierre();
            }
        };

        document.addEventListener('submit', async (e) => {
            const form = e.target;
            if (!form || !form.matches || !form.matches('form[data-confirm-message]')) return;
            if (form.dataset.confirmado === '1') return;

            e.preventDefault();
            const ok = await window.mostrarConfirmacion({
                titulo: form.dataset.confirmTitle || 'Confirmar acción',
                mensaje: form.dataset.confirmMessage,
                textoConfirmar: form.dataset.confirmCta || 'Confirmar',
                variante: form.dataset.confirmVariant || 'danger',
            });
            if (ok) {
                form.dataset.confirmado = '1';
                form.submit();
            }
        });
    })();
</script>
