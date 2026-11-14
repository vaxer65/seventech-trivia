"""Player score regressions using deterministic, browser-only API fixtures."""

from browser_test_case import BrowserTestCase


class PlayerScoreTest(BrowserTestCase):
    def show_finished_team(self, score):
        payload = {
            "autenticado": True,
            "estado_juego": "finalizado",
            "segundos_globales": None,
            "posicion_actual": 20,
            "total_equipos": 200,
            "equipo": {
                "nombre_equipo": "A mobile team with a long display name",
                "puntaje_total": score,
                "aciertos_totales": 150,
                "tiempo_acumulado_ms": 3599000,
                "pin_recuperacion": "0000",
                "finalizo_carrera": True,
            },
        }
        self.context.route(
            "**/api/**",
            lambda route: route.fulfill(json=payload)
            if "/api/estado-juego" in route.request.url
            else route.abort(),
        )
        self.visit("/jugar")
        self.page.locator("#vista-final").wait_for(state="visible")
        self.page.evaluate("document.fonts.ready")

    def test_complete_score_and_time_fit_mobile_and_desktop(self):
        for score in [0, 10000, 10000000]:
            self.show_finished_team(score)
            for width in [320, 360, 375, 390, 412, 768, 1440]:
                with self.subTest(score=score, width=width):
                    self.page.set_viewport_size({"width": width, "height": 740})
                    for selector in [
                        "#header-puntaje",
                        "#puntaje-final-texto",
                        "#aciertos-final-texto",
                        "#tiempo-final-texto",
                    ]:
                        dimensions = self.page.locator(selector).evaluate(
                            """element => ({ available: element.clientWidth,
                                content: element.scrollWidth,
                                left: element.getBoundingClientRect().left,
                                right: element.getBoundingClientRect().right })"""
                        )
                        self.assertLessEqual(
                            dimensions["content"], dimensions["available"] + 1, selector
                        )
                        self.assertGreaterEqual(dimensions["left"], 0)
                        self.assertLessEqual(dimensions["right"], width)
                    self.assertEqual(
                        self.page.locator("#puntaje-final-texto").inner_text(),
                        f"{score} PTS",
                    )
            self.context.unroute("**/api/**")
