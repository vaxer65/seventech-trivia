# Browser regressions

These tests use Python's `unittest` and Playwright with Chromium. Install Playwright
in a virtual environment and install its Chromium browser before running them.

Start an isolated Laravel preview on `http://127.0.0.1:8813` using a separate
test database seeded with `DatabaseSeeder`. Never point browser tests or PHPUnit
at production. Keep the preview database separate from the PHPUnit database
instance because `RefreshDatabase` rebuilds tables.

The preview administrator hash must match `browser-test-password`, or set
`BROWSER_ADMIN_PASSWORD` to the corresponding test-only password. Configure
Spanish locale, file sessions and HTTP cookies for the local preview through
process environment variables; no production environment file is required.

Run the regressions with the preview running:

```bash
PYTHONDONTWRITEBYTECODE=1 python3 -m unittest discover -s tests/browser -v
```

`BROWSER_BASE_URL` overrides the preview URL; the tests reject non-loopback hosts.
`BROWSER_EXECUTABLE_PATH` optionally selects an existing Chromium installation.

The checks cover small and desktop layouts, validation-related notification
lifecycle, mouse/touch interaction, keyboard focus, modal confirmation and the
popover fallback. Touch emulation does not replace tests on physical iOS devices.
