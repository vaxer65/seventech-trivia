"""Exercise the real local team-removal form and its success notification."""

from uuid import uuid4

from playwright.sync_api import expect

from browser_test_case import BrowserTestCase


class TeamRemovalFeedbackTest(BrowserTestCase):
    def test_team_removal_success_notification_expires(self):
        team_name = "Removal fixture " + uuid4().hex[:8]
        self.visit("/jugar")
        token = self.page.locator('meta[name="csrf-token"]').get_attribute("content")
        response = self.context.request.post(
            self.base_url + "/api/registrar-equipo",
            data={
                "nombre_equipo": team_name,
                "integrantes": ["Test Player One", "Test Player Two"],
            },
            headers={"Accept": "application/json", "X-CSRF-TOKEN": token},
        )
        self.assertTrue(response.ok, f"Registration returned HTTP {response.status}")
        self.login_admin()
        card = self.page.get_by_role("heading", name=team_name, exact=True).locator(
            "xpath=ancestor::div[.//form[starts-with(@action, '/admin/equipo/')]][1]"
        )
        card.get_by_role("button", name="Dar de baja").click()
        with self.page.expect_navigation():
            self.page.locator("#modal-confirmacion-aceptar").click()
        notification = self.page.locator(".ui-toast").filter(
            has_text="Equipo eliminado correctamente."
        )
        expect(notification).to_be_visible()
        expect(self.page.get_by_role("heading", name=team_name, exact=True)).to_have_count(0)
        expect(notification).to_have_count(0, timeout=6500)
