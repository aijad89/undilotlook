const { test, expect } = require('@playwright/test');
const path = require('path');
const { signInAs, watchErrors } = require('./helpers');
const { seed } = require('./fixtures/seed');

/**
 * Joining is one form shown a question at a time. The steps are switched in
 * the browser, so only a browser can check them: Next waits for the declaration,
 * then the certificates, then contact and photos, then payment; Previous goes back, and Join appears on
 * the last step only.
 */

/** A real 1x1 PNG: the server reads what a file is, not its name. */
const PNG = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', 'base64');
const photos = n => Array.from({ length: n }, (_, i) => ({ name: `product${i + 1}.png`, mimeType: 'image/png', buffer: PNG }));

/**
 * Every step after the declaration at its plainest, one question each: no
 * recognition, no classification certificate, a contact, five photos.
 */
async function plainAnswers(form) {
    const next = form.getByRole('button', { name: 'Next' });
    await form.getByLabel('Not applicable').first().check();
    await next.click();
    await form.locator('input[name=classification][value=no]').check();
    await next.click();
    await form.getByLabel('Name', { exact: true }).fill('Siti Aminah');
    await form.getByLabel('Mobile number').fill('012-345 6789');
    await next.click();
    await form.locator('#product_images').setInputFiles(photos(5));
    await next.click();
}

test('a participant walks the join steps with Previous and Next, then sends', async ({ page }) => {
    const eventId = seed('join-setup.php').trim().split('\n').pop();
    const errors = watchErrors(page);

    await signInAs(page, 'tenun@e2e.test');
    await page.goto(`/events/${eventId}`);

    const form = page.locator('#join-form');
    const next = form.getByRole('button', { name: 'Next' });
    const prev = form.getByRole('button', { name: 'Previous' });
    const join = page.getByRole('button', { name: 'Join this event' });
    const step = n => expect(form.getByText(`Step ${n} of 7`)).toBeVisible();

    // A tenun maker: declaration, recognition, classification, quality,
    // contact, photos, payment.
    await step(1);
    await expect(prev).toBeDisabled();
    await expect(next).toBeDisabled();
    await expect(join).toBeHidden();

    // Ticked and unticked: Next follows the declaration.
    const declare = form.getByLabel(/I want to join this event, and I declare/);
    await declare.check();
    await expect(next).toBeEnabled();
    await declare.uncheck();
    await expect(next).toBeDisabled();
    await declare.check();
    await next.click();

    // One question at a time: unanswered holds Next.
    await step(2);
    await expect(declare).toBeHidden();
    await expect(form.getByText('Do you hold a Malaysian Handicraft')).toBeHidden();
    await next.click();
    await step(2);
    await form.getByLabel('Adiguru Kraf').check();
    await next.click();

    // Yes to the classification certificate: its number, dates and file are needed.
    await step(3);
    await form.locator('input[name=classification][value=yes]').check();
    await expect(form.locator('#classification_number')).toBeVisible();
    await expect(form.locator('#classification_file')).toHaveJSProperty('required', true);
    await next.click();
    await step(3);
    // No: the certificate fields go, and Next goes on.
    await form.locator('input[name=classification][value=no]').check();
    await expect(form.locator('#classification_number')).toBeHidden();
    await next.click();

    // The quality question, with its own standard only.
    await step(4);
    await expect(form.getByText('Songket / Tenunan Standard entrepreneur')).toBeVisible();
    await expect(form.getByText('Batik Standard entrepreneur')).toHaveCount(0);
    await form.locator('input[name=quality][value=none]').check();
    await expect(form.locator('#quality_number')).toBeHidden();
    await next.click();

    await step(5);
    await form.getByLabel('Name', { exact: true }).fill('Siti Aminah');
    await form.getByLabel('Mobile number').fill('012-345 6789');
    await next.click();

    // Four photos are not a set; five go on.
    await step(6);
    await form.locator('#product_images').setInputFiles(photos(4));
    await next.click();
    await step(6);
    await expect(form.locator('#product_images')).toHaveJSProperty('validity.customError', true);
    await form.locator('#product_images').setInputFiles(photos(5));
    await next.click();

    await step(7);
    await expect(form.getByText('How did you pay?')).toBeVisible();
    await expect(next).toBeHidden();
    await expect(join).toBeVisible();

    await prev.click();
    await step(6);
    await expect(join).toBeHidden();
    await next.click();

    // "How did you pay?" is required, waiver included: Join with nothing picked
    // sends nothing, and a waiver with no category is held too.
    await join.click();
    await step(7);
    await expect(form.locator('input[name=payment_method]').first()).toHaveJSProperty('validity.valueMissing', true);
    await form.getByLabel('Fee waiver').check();
    await join.click();
    await expect(form.locator('input[name=waiver_category]').first()).toHaveJSProperty('validity.valueMissing', true);
    expect(await page.evaluate(() => document.querySelectorAll('.evd-msg-wait').length)).toBe(0);

    await form.getByLabel('Online transfer').check();
    await form.locator('#receipt').setInputFiles({ name: 'slip.pdf', mimeType: 'application/pdf', buffer: Buffer.from('%PDF-1.4 slip') });
    await Promise.all([page.waitForNavigation(), join.click()]);

    await expect(page.getByText('The office is reviewing your documents')).toBeVisible();
    expect(errors).toEqual([]);
});

test('paying asks for the receipt only; a fee waiver asks for a category and documents', async ({ page }) => {
    const eventId = seed('join-setup.php').trim().split('\n').pop();
    const errors = watchErrors(page);

    await signInAs(page, 'logam@e2e.test');
    await page.goto(`/events/${eventId}`);

    const form = page.locator('#join-form');
    await form.getByLabel(/I want to join this event, and I declare/).check();
    await form.getByRole('button', { name: 'Next' }).click();

    // Not a textile craft: no quality question, so six steps.
    await expect(form.getByText('Step 2 of 6')).toBeVisible();
    await expect(form.getByText('Quality compliance certificate')).toHaveCount(0);
    await plainAnswers(form);
    await expect(form.getByText('Step 6 of 6')).toBeVisible();

    // Paying: the receipt, and no supporting documents.
    await form.getByLabel('Cheque').check();
    await expect(form.locator('#receipt')).toBeVisible();
    await expect(form.locator('#documents')).toBeHidden();

    await form.getByLabel('Fee waiver').check();
    await expect(form.locator('#receipt')).toBeHidden();
    await expect(form.locator('#documents')).toBeVisible();
    await expect(form.getByText('Which waiver applies to you?')).toBeVisible();

    // A waiver needs its documents, whatever the category.
    await expect(form.locator('#documents')).toHaveJSProperty('required', true);
    await form.getByLabel('First time joining a promotion with Kraftangan Malaysia', { exact: true }).check();
    await expect(form.locator('#documents')).toHaveJSProperty('required', true);

    // Back to paying: the receipt returns and is required again.
    await form.getByLabel('Cash').check();
    await expect(form.locator('#receipt')).toBeVisible();
    await expect(form.locator('#receipt')).toHaveJSProperty('required', true);

    await form.getByLabel('Fee waiver').check();
    await form.locator('#documents').setInputFiles({ name: 'letter.pdf', mimeType: 'application/pdf', buffer: Buffer.from('%PDF-1.4 letter') });
    await Promise.all([page.waitForNavigation(), page.getByRole('button', { name: 'Join this event' }).click()]);
    await expect(page.getByText('The office is reviewing your documents')).toBeVisible();
    expect(errors).toEqual([]);
});

test('Save draft keeps the answers so far and comes back on the same step', async ({ page }) => {
    const eventId = seed('join-setup.php').trim().split('\n').pop();
    const errors = watchErrors(page);

    await signInAs(page, 'kayu@e2e.test');
    await page.goto(`/events/${eventId}`);

    const form = page.locator('#join-form');
    const next = form.getByRole('button', { name: 'Next' });
    await form.getByLabel(/I want to join this event, and I declare/).check();
    await next.click();
    await form.getByLabel('Tokoh Kraf Negara').check();
    await next.click();
    await form.locator('input[name=classification][value=no]').check();
    await next.click();

    // Half-way through the contact step, nothing required is checked.
    await form.getByLabel('Name', { exact: true }).fill('Ahmad Kayu');
    await Promise.all([page.waitForNavigation(), form.getByRole('button', { name: 'Save draft' }).click()]);

    await expect(page.getByText('Draft saved.')).toBeVisible();
    await expect(form.getByText('Step 4 of 6')).toBeVisible();
    await expect(form.getByLabel('Name', { exact: true })).toHaveValue('Ahmad Kayu');

    // Later: back from the start, answers in place, and not sent.
    await page.goto(`/events/${eventId}`);
    await expect(page.getByText('You saved a draft on')).toBeVisible();
    await expect(form.getByText('Step 1 of 6')).toBeVisible();
    await form.getByLabel(/I want to join this event, and I declare/).check();
    await next.click();
    await expect(form.getByLabel('Tokoh Kraf Negara')).toBeChecked();
    expect(errors).toEqual([]);
});
