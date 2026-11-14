"""Responsive regressions for public screens and the authenticated question bank."""

from browser_test_case import BrowserTestCase


class MobileLayoutTest(BrowserTestCase):
    def assert_fits_viewport(self):
        dimensions = self.page.evaluate(
            """() => ({ viewport: innerWidth,
                content: document.documentElement.scrollWidth })"""
        )
        self.assertLessEqual(dimensions["content"], dimensions["viewport"] + 1)

    def test_public_screens_fit_mobile_and_desktop_widths(self):
        for path in ["/", "/jugar", "/espectador"]:
            self.visit(path)
            for width, height in [(320, 568), (360, 740), (390, 844), (740, 360), (1440, 900)]:
                with self.subTest(path=path, width=width):
                    self.page.set_viewport_size({"width": width, "height": height})
                    self.assert_fits_viewport()

    def test_admin_filters_and_question_actions_fit_small_screens(self):
        self.login_admin()
        for width in [320, 360, 375, 390, 412, 768, 1440]:
            with self.subTest(width=width):
                self.page.set_viewport_size({"width": width, "height": 740})
                self.assert_fits_viewport()
                for selector in ["#filtro-modulo", "#filtro-nivel"]:
                    bounds = self.page.locator(selector).bounding_box()
                    self.assertGreaterEqual(bounds["x"], 0)
                    self.assertLessEqual(bounds["x"] + bounds["width"], width + 1)
                self.assertTrue(self.page.locator("details summary").first.is_visible())

    def test_question_editor_stays_inside_a_narrow_viewport(self):
        self.page.set_viewport_size({"width": 320, "height": 568})
        self.login_admin()
        self.page.locator("details summary").first.click()
        self.assert_fits_viewport()
        self.assertTrue(self.page.locator("details[open] form").first.is_visible())

    def test_player_viewport_allows_user_zoom(self):
        self.visit("/jugar")
        viewport = self.page.locator('meta[name="viewport"]').get_attribute("content")
        self.assertNotIn("user-scalable=no", viewport)
        self.assertNotIn("maximum-scale=1", viewport)
