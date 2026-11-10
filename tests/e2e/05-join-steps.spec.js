const { test, expect } = require('@playwright/test');
const path = require('path');
const { signInAs, watchErrors } = require('./helpers');
const { seed } = require('./fixtures/seed');

/**
 * Joining is one form shown a question at a time. The steps are switched in
 * the browser, so only a browser can check them: No goes nowhere, Yes leads to
 * payment, Previous goes back, and Join appears on the last step only.
 */
test('a participant walks the join steps with Previous and Next, then sends', async ({ page }) => {
    const eventId = seed('join-setup.php').trim().split('\n').pop();
    const errors = watchErrors(page);

    await signInAs(page, 'tenun@e2e.test');
    await page.goto(`/events/${eventId}`);

    const form = page.locator('#join-form');
    const next = form.getByRole('button', { name: 'Next' });
    const prev = form.getByRole('button', { name: 'Previous' });
    const join = page.getByRole('button', { name: 'Join this event' });

    await expect(form.getByText('Step 1 of 2')).toBeVisible();
    await expect(prev).toBeDisabled();
    await expect(next).toBeDisabled();
    await expect(join).toBeHidden();

    await form.getByLabel('No').check();
    await expect(form.getByText('No problem.')).toBeVisible();
    await expect(next).toBeDisabled();

    await form.getByLabel('Yes').check();
    await next.click();
    await expect(form.getByText('Step 2 of 2')).toBeVisible();
    await expect(form.getByText('How did you pay?')).toBeVisible();
    await expect(form.getByText('Do you want to join this event?')).toBeHidden();
    await expect(next).toBeHidden();
    await expect(join).toBeVisible();

    await prev.click();
    await expect(form.getByText('Step 1 of 2')).toBeVisible();
    await expect(join).toBeHidden();
    await next.click();

    await form.getByLabel('Online transfer').check();
    await form.locator('#receipt').setInputFiles({ name: 'slip.pdf', mimeType: 'application/pdf', buffer: Buffer.from('%PDF-1.4 slip') });
    await Promise.all([page.waitForNavigation(), join.click()]);

    await expect(page.getByText('The office is reviewing your documents')).toBeVisible();
    expect(errors).toEqual([]);
});
