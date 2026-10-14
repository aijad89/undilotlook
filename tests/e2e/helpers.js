const { expect } = require('@playwright/test');

/**
 * Sign in as a seeded account through /auto — no password is ever typed.
 * E2eSeeder's accounts: e2e-super@, e2e-hq@, e2e-admin@ (Johor), and the
 * participants tenun@, logam@, kayu@ — all on e2e.test.
 */
async function signInAs(page, email) {
    await page.goto('/auto');
    const card = page.locator(`form:has(.email:text-is("${email}")) button`);
    await expect(card, `no /auto card for ${email}`).toHaveCount(1);
    await Promise.all([page.waitForNavigation(), card.click()]);
}

/**
 * Accept the next native dialog AND REMEMBER WHAT IT SAID.
 *
 * Never `page.once('dialog', d => d.accept())`. An `alert` is a dialog too: a
 * handler that bails out early with alert('select something first') is accepted
 * blindly, nothing is submitted, and the test fails four lines later on
 * something unrelated — while the page had said exactly what was wrong.
 *
 *     const dialog = acceptDialog(page);
 *     await page.getByRole('button', { name: 'Open voting' }).click();
 *     expect(dialog.type, `page said: ${dialog.message}`).toBe('confirm');
 *
 * Every `alert` it accepts is kept for strayAlerts(), read once per file in
 * afterEach.
 */
const STRAY_ALERTS = new WeakMap();

function acceptDialog(page) {
    const seen = { type: null, message: null };

    page.once('dialog', (dialog) => {
        seen.type = dialog.type();
        seen.message = dialog.message();

        if (dialog.type() === 'alert') {
            STRAY_ALERTS.set(page, [...(STRAY_ALERTS.get(page) || []), dialog.message()]);
        }

        dialog.accept();
    });

    return seen;
}

/** Every alert this page showed to an acceptDialog() caller. Fresh per test. */
function strayAlerts(page) {
    return STRAY_ALERTS.get(page) || [];
}

/** Uncaught page errors and console errors, collected from the moment it is called. */
function watchErrors(page) {
    const errors = [];
    page.on('pageerror', (e) => errors.push(e.message));
    page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });
    return errors;
}

module.exports = { signInAs, acceptDialog, strayAlerts, watchErrors };
