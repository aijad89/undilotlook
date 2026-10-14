#!/usr/bin/env node
//
// The isolated environment the browser suite runs against, as one process.
//
// In `daftar` three consecutive causes of browser-suite failures were hand setup,
// not code: fixtures seeding one database while the browser read another; uploads
// failing because `artisan serve` drops TMP/TEMP when APP_ENV is set; and no
// notification ever arriving because nothing ran `queue:work`. Each was remembered
// once and forgotten once. This file is the remembering: playwright.config.js
// names it in `webServer`, so an isolated run is provided, not assembled.
//
// THE ENVIRONMENT IS A FILE, `.env.e2e`, WRITTEN HERE ON EVERY RUN. Process
// variables are not enough: `artisan serve` hands its PHP child only an allowlist
// of them. APP_ENV gets through and DB_DATABASE does not, so on the first run of
// this suite the server read `.env` and served the DEVELOPMENT database while the
// fixtures wrote to the copy. With APP_ENV=e2e, Laravel loads `.env.e2e` in every
// process, the server's child included. It is `.env` with the e2e keys replaced;
// .gitignore keeps it off the repo, since it carries the app key and DB password.

const fs = require('fs');
const { spawn, spawnSync } = require('child_process');
const path = require('path');
const { E2E_ENV } = require('./env');

const ROOT = path.resolve(__dirname, '..', '..');
const PORT = process.env.PLAYWRIGHT_PORT || '8124';
const HOST = '127.0.0.1';
const PHP = process.env.PHP_BINARY || 'php';

const overrides = { ...E2E_ENV, APP_URL: `http://${HOST}:${PORT}` };
const base = fs.readFileSync(path.join(ROOT, '.env'), 'utf8')
    .split(/\r?\n/)
    .filter((line) => !Object.keys(overrides).some((key) => line.startsWith(`${key}=`)));
fs.writeFileSync(
    path.join(ROOT, '.env.e2e'),
    [...base, '', '# Written by tests/e2e/serve.js — do not edit; it is rewritten every run.',
        ...Object.entries(overrides).map(([key, value]) => `${key}=${value}`), ''].join('\n'),
);

const env = { ...process.env, ...overrides };

function php(args, label) {
    process.stdout.write(`[serve] ${label}\n`);
    const result = spawnSync(PHP, args, { cwd: ROOT, env, stdio: 'inherit', shell: true });

    if (result.status !== 0) {
        process.stderr.write(`[serve] FAILED: ${PHP} ${args.join(' ')} exited ${result.status}\n`);
        process.exit(1);
    }
}

// ── 1. A known database, every time ──────────────────────────────────────────
// A run that starts from whatever the last run left is a run whose result
// depends on history. The fixture runner refuses to touch anything but the copy.
php(['tests/e2e/fixtures/run.php', 'ensure-database.php'], `making sure ${E2E_ENV.DB_DATABASE} exists`);
php(['artisan', 'migrate:fresh', '--seed', '--seeder=Database\\Seeders\\E2eSeeder', '--force'], `resetting ${E2E_ENV.DB_DATABASE} and seeding it`);

// ── 2. The application server ────────────────────────────────────────────────
process.stdout.write(`[serve] starting the application on http://${HOST}:${PORT}\n`);
const server = spawn(PHP, ['artisan', 'serve', `--host=${HOST}`, `--port=${PORT}`], { cwd: ROOT, env, stdio: 'inherit', shell: true });

// ── 3. The queue worker ──────────────────────────────────────────────────────
// Every notification is queued; without this nothing is ever delivered.
// --tries=1: a test run wants the first failure. --sleep=1: a spec waiting for a
// broadcast should not wait three seconds for the worker to look.
process.stdout.write('[serve] starting the queue worker\n');
const worker = spawn(PHP, ['artisan', 'queue:work', '--tries=1', '--sleep=1'], { cwd: ROOT, env, stdio: 'inherit', shell: true });

// ── One lifetime ─────────────────────────────────────────────────────────────
// The children go when Playwright stops this process, or the next run finds the
// port taken and a worker still holding the old database.
let stopping = false;

function stop(code) {
    if (stopping) return;
    stopping = true;

    for (const child of [server, worker]) {
        if (child.pid && !child.killed) {
            try {
                // On Windows a signal does not reach the grandchildren `shell: true`
                // creates, so the tree is taken by pid.
                if (process.platform === 'win32') {
                    spawnSync('taskkill', ['/pid', String(child.pid), '/T', '/F'], { stdio: 'ignore' });
                } else {
                    child.kill('SIGTERM');
                }
            } catch { /* already gone */ }
        }
    }

    process.exit(code);
}

for (const signal of ['SIGINT', 'SIGTERM', 'SIGHUP']) {
    process.on(signal, () => stop(0));
}
process.on('exit', () => stop(0));

server.on('exit', (code) => { process.stderr.write(`[serve] the application server exited (${code})\n`); stop(code ?? 1); });
worker.on('exit', (code) => { process.stderr.write(`[serve] the queue worker exited (${code})\n`); stop(code ?? 1); });
