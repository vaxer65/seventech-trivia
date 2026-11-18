"""Shared browser lifecycle for checks against an isolated local preview."""

import os
import unittest
from urllib.parse import urlparse

from playwright.sync_api import sync_playwright


class BrowserTestCase(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.base_url = os.environ.get("BROWSER_BASE_URL", "http://127.0.0.1:8813")
        if urlparse(cls.base_url).hostname not in {"localhost", "127.0.0.1", "::1"}:
            raise RuntimeError("Browser regression tests require a local preview.")
        cls.playwright = sync_playwright().start()
        options = {"headless": True}
        if executable := os.environ.get("BROWSER_EXECUTABLE_PATH"):
            options["executable_path"] = executable
        cls.browser = cls.playwright.chromium.launch(**options)

    @classmethod
    def tearDownClass(cls):
        cls.browser.close()
        cls.playwright.stop()

    def setUp(self):
        self.context = self.browser.new_context(base_url=self.base_url)
        self.addCleanup(self.context.close)
        self.page = self.context.new_page()

    def visit(self, path):
        self.page.goto(path)
        self.page.wait_for_load_state("networkidle")

    def login_admin(self):
        self.visit("/admin")
        self.page.locator('input[name="password"]').fill(
            os.environ.get("BROWSER_ADMIN_PASSWORD", "browser-test-password")
        )
        self.page.get_by_role("button", name="Entrar al Panel de Control").click()
        self.page.wait_for_load_state("networkidle")
        self.assertTrue(self.page.locator('form[action="/admin/logout"]').is_visible())
