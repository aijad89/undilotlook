const { defineConfig, devices } = require('@playwright/test');
const { E2E_ENV } = require('./tests/e2e/env');

// The e2e environment goes on THIS process too, not only on the server: specs
// shell out to tests/e2e/fixtures/run.php, and those children inherit from here.
// Without it they would read `.env` and write into the development database.
Object.assign(process.env, E2E_ENV);

const PORT = process.env.PLAYWRIGHT_PORT || '8124';

module.exports = defineConfig({
    testDir: './tests/e2e',

    // One database, shared and mutated in file order — so one worker, in order.
    fullyParallel: false,
    workers: 1,
    forbidOnly: !!process.env.CI,

    // ONE RETRY, AS A MEASUREMENT, NOT A MUTE. Playwright reports a test that failed
    // and then passed as `flaky`, on its own line apart from `passed`. One green run
    // is not proof on a browser suite: in `daftar` four full runs of unchanged code
    // gave four different sets of failures. READ THE FLAKY COUNT. Flaky > 0 is not
    // a clean pass; it is a second attempt. A genuinely broken spec fails both and
    // is still red.
    retries: 1,
    reporter: 'list',

    use: {
        baseURL: `http://127.0.0.1:${PORT}`,
        trace: 'on-first-retry',
    },

    // serve.js owns the database reset, the server and the queue worker — one
    // lifetime. Never reuse a server started by hand: that is how a run gets the
    // right port and the wrong database.
    webServer: {
        command: 'node tests/e2e/serve.js',
        url: `http://127.0.0.1:${PORT}/login`,
        reuseExistingServer: false,
        timeout: 180 * 1000,
        stdout: 'pipe',
        stderr: 'pipe',
        env: { ...E2E_ENV, PLAYWRIGHT_PORT: PORT },
    },

    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
