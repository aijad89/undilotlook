const { test, expect } = require('@playwright/test');
const { signInAs, acceptDialog, strayAlerts, watchErrors } = require('./helpers');
const { seed } = require('./fixtures/seed');

// The zone checks a company and puts it on the draw list; HQ checks it again.
// One company through the whole round, in order — each test hands its state to
// the next, so the file runs serially:
//   zone adds it with a payment note → HQ returns it with a note → the zone
//   fixes it → the participant is told it is being added → HQ verifies → the
//   zone can no longer remove it, and the participant sees the draw.

test.describe.configure({ mode: 'serial' });

test.afterEach(async ({ page }) => {
    expect(strayAlerts(page), 'an alert was accepted where a confirm was expected').toEqual([]);
});

let eventId, title, company, email;

test.beforeAll(() => {
    [eventId, title, company, email] = seed('two-step-setup.php').trim().split('\n').slice(-4).map((s) => s.trim());
});

const ZONE = 'e2e-admin@e2e.test';
const HQ = 'e2e-hq@e2e.test';
const drawList = () => `/admin/events/${eventId}/registrations`;
const row = (page) => page.locator('table:has(th:text-is("HQ check")) tbody tr', { hasText: company });
const submitting = (page, action) => Promise.all([page.waitForNavigation(), action]);

test('the zone adds the company with its booth and how it paid; it waits for HQ', async ({ page }) => {
    const errors = watchErrors(page);
    await signInAs(page, ZONE);
    await page.goto(drawList());

    const value = await page.locator('#company_id option', { hasText: company }).getAttribute('value');
    await page.selectOption('#company_id', value);
    await page.selectOption('#plot_size', '3x6');
    await submitting(page, page.locator('button:text-is("Add")').click());
    await expect(page.getByText('HQ will verify before the company is told.')).toBeVisible();

    await row(page).locator('input[name=payment_note]').fill('Receipt 00123, transfer 3 Nov');
    await submitting(page, row(page).locator('input[name=payment_note]').press('Enter'));

    await expect(row(page)).toContainText('Waiting for HQ');
    await expect(row(page).locator('input[name=payment_note]')).toHaveValue('Receipt 00123, transfer 3 Nov');

    // The table fits its card: nothing, Actions included, scrolls out of sight.
    const fits = await page.locator('.table-responsive:has(th:text-is("HQ check"))')
        .evaluate((box) => box.scrollWidth <= box.clientWidth);
    expect(fits, 'the draw list scrolls sideways').toBe(true);

    expect(errors).toEqual([]);
});

test('HQ finds it on the dashboard and returns it to the zone with a note', async ({ page }) => {
    await signInAs(page, HQ);
    await submitting(page, page.getByRole('link', { name: `${title}: 1 draw-list row waiting for HQ to verify` }).click());

    await expect(row(page).locator('input[name=payment_note]')).toHaveValue('Receipt 00123, transfer 3 Nov');
    await row(page).locator('summary', { hasText: 'Return' }).click();
    await row(page).locator('input[name=hq_note]').fill('Booth should be 3x3');
    await submitting(page, row(page).locator('input[name=hq_note]').press('Enter'));

    await expect(page.getByText(`${company} returned to its zone.`)).toBeVisible();
});

test('the zone sees it returned, fixes the booth, and it waits for HQ again', async ({ page }) => {
    await signInAs(page, ZONE);
    await submitting(page, page.getByRole('link', { name: `${title}: 1 draw-list row returned by HQ` }).click());

    await expect(row(page)).toContainText('Returned by HQ');
    await expect(row(page)).toContainText('Booth should be 3x3');

    await row(page).locator('select[name=plot_size]').selectOption('3x3');
    await submitting(page, row(page).locator('button[title^="Save — HQ"]').click());

    await expect(page.getByText('Saved. Waiting for HQ to verify.')).toBeVisible();
    // Fixed, it has left the "returned" view it was opened from.
    await expect(row(page)).toHaveCount(0);
    await page.goto(drawList());
    await expect(row(page)).toContainText('Waiting for HQ');
});

test('the participant is told only that the office is adding it', async ({ page }) => {
    await signInAs(page, email);
    await page.goto(`/events/${eventId}`);

    await expect(page.getByText('The office is adding your company to this draw.')).toBeVisible();
    await expect(page.getByText('I want to join this event, and I declare')).toHaveCount(0);
});

test('HQ verifies it', async ({ page }) => {
    await signInAs(page, HQ);
    await page.goto(drawList());

    await submitting(page, row(page).locator('button[title^="Verify"]').click());

    await expect(page.getByText('Saved and verified.')).toBeVisible();
    await expect(row(page)).toContainText('Verified');
});

test('the zone can no longer take it off the list, and the participant sees the draw', async ({ page }) => {
    await signInAs(page, ZONE);
    await page.goto(drawList());

    await expect(row(page).locator('button[title="Remove from the draw list"]')).toHaveCount(0);

    await row(page).locator('.tick-row').check();
    await page.selectOption('#bulk-action', 'remove');
    const dialog = acceptDialog(page);
    await submitting(page, page.getByRole('button', { name: 'Apply' }).click());
    expect(dialog.type, `page said: ${dialog.message}`).toBe('confirm');
    await expect(page.getByText('1 kept: only HQ can remove them now.')).toBeVisible();
    await expect(row(page)).toHaveCount(1);

    await signInAs(page, email);
    await page.goto('/dashboard');
    await expect(page.getByText(title)).toBeVisible();
});
