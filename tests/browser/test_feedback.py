"""Browser regressions for shared toast and confirmation interactions."""

from playwright.sync_api import expect

from browser_test_case import BrowserTestCase


class FeedbackTest(BrowserTestCase):
    def setUp(self):
        super().setUp()
        self.visit('/jugar')
        self.page.clock.install()

    def show_toast(self, message='Notificación de prueba', kind='success'):
        self.page.evaluate('([message, kind]) => mostrarToast(message, kind)', [message, kind])
        self.page.clock.run_for(300)
        return self.page.locator('#' + self.page.locator('.ui-toast').last.get_attribute('id'))

    def test_success_and_error_expire_automatically(self):
        self.show_toast('Guardado')
        self.show_toast('No se pudo guardar', 'error')
        self.page.clock.run_for(4800)
        expect(self.page.locator('.ui-toast')).to_have_count(1)
        expect(self.page.locator('.ui-toast')).to_contain_text('No se pudo guardar')
        self.page.clock.run_for(3500)
        expect(self.page.locator('.ui-toast')).to_have_count(0)

    def test_touch_tap_does_not_leave_success_open(self):
        context = self.browser.new_context(base_url=self.base_url, viewport={'width': 375, 'height': 667}, is_mobile=True, has_touch=True)
        self.addCleanup(context.close)
        page = context.new_page()
        page.goto('/jugar')
        page.wait_for_load_state('networkidle')
        page.clock.install()
        page.evaluate("mostrarToast('Guardado')")
        page.clock.run_for(300)
        page.locator('.ui-toast span').last.tap()
        page.clock.run_for(5000)
        expect(page.locator('.ui-toast')).to_have_count(0)

    def test_long_toasts_do_not_overlap_after_resize(self):
        self.page.set_viewport_size({'width': 390, 'height': 844})
        for index in range(3):
            self.show_toast(f'{index}: ' + 'Esta notificación incluye un mensaje largo que debe poder leerse completo. ' * 2, 'error')
        self.page.set_viewport_size({'width': 320, 'height': 568})
        self.page.clock.run_for(300)
        self.assert_toasts_fit()
        self.page.set_viewport_size({'width': 667, 'height': 320})
        self.page.clock.run_for(300)
        self.assert_toasts_fit()

    def assert_toasts_fit(self):
        boxes = sorted(self.page.locator('.ui-toast').evaluate_all('(items) => items.map(item => { const r = item.getBoundingClientRect(); return {top:r.top,bottom:r.bottom,left:r.left,right:r.right}; })'), key=lambda box: box['top'])
        viewport = self.page.viewport_size
        for box in boxes:
            self.assertGreaterEqual(box['top'], 0)
            self.assertLessEqual(box['bottom'], viewport['height'])
            self.assertGreaterEqual(box['left'], 0)
            self.assertLessEqual(box['right'], viewport['width'])
        for previous, current in zip(boxes, boxes[1:]):
            self.assertGreaterEqual(current['top'] - previous['bottom'], 8)

    def test_visible_queue_preserves_focused_toast(self):
        first = self.show_toast('Primero', 'error')
        first.get_by_role('button').focus()
        for message in ['Segundo', 'Tercero', 'Cuarto']:
            self.show_toast(message, 'error')
        expect(self.page.locator('.ui-toast')).to_have_count(3)
        expect(first.get_by_role('button')).to_be_focused()
        first.get_by_role('button').click()
        expect(self.page.locator('.ui-toast')).to_have_count(3)
        expect(self.page.locator('.ui-toast').last).to_contain_text('Cuarto')
        expect(self.page.locator('.ui-toast').first.get_by_role('button')).to_be_focused()

    def test_mouse_hover_and_keyboard_focus_pause_then_resume(self):
        toast = self.show_toast()
        toast.hover()
        self.page.clock.run_for(6000)
        expect(toast).to_be_visible()
        self.page.mouse.move(0, 0)
        self.page.clock.run_for(5000)
        expect(self.page.locator('.ui-toast')).to_have_count(0)
        toast = self.show_toast(kind='error')
        toast.get_by_role('button').focus()
        self.page.clock.run_for(9000)
        expect(toast).to_be_visible()
        self.page.locator('#reg-nombre-equipo').focus()
        self.page.clock.run_for(8200)
        expect(self.page.locator('.ui-toast')).to_have_count(0)

    def test_manual_close_restores_origin_focus(self):
        origin = self.page.locator('#reg-nombre-equipo')
        origin.focus()
        toast = self.show_toast()
        toast.get_by_role('button').click()
        expect(self.page.locator('.ui-toast')).to_have_count(0)
        expect(origin).to_be_focused()

    def test_modal_confirmation_defers_toasts_and_preserves_cancel(self):
        self.page.evaluate("window.confirmResult = null; mostrarConfirmacion({mensaje: '¿Continuar?'}).then(value => window.confirmResult = value); mostrarToast('En espera');")
        expect(self.page.locator('.ui-toast')).to_have_count(0)
        self.page.locator('#modal-confirmacion-aceptar').click()
        self.page.wait_for_function('window.confirmResult === true')
        expect(self.page.locator('.ui-toast')).to_contain_text('En espera')
        self.page.evaluate("() => { mostrarConfirmacion({mensaje: '¿Continuar?'}).then(value => window.confirmResult = value); }")
        self.page.locator('#modal-confirmacion-cancelar').click()
        self.page.wait_for_function('window.confirmResult === false')

    def test_popover_fallback_displays_and_closes(self):
        self.page.evaluate("HTMLElement.prototype.showPopover = undefined; HTMLElement.prototype.hidePopover = undefined; document.ariaNotify = undefined")
        toast = self.show_toast('Aviso alternativo')
        expect(toast).to_be_visible()
        expect(self.page.locator('#toast-aria-fallback')).to_have_text('Aviso alternativo')
        toast.get_by_role('button').click()
        expect(self.page.locator('.ui-toast')).to_have_count(0)
