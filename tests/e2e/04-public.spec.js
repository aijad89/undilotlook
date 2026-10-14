const { test, expect } = require('@playwright/test');
const { watchErrors } = require('./helpers');

test('a visitor sees the published events and can reach the sign-in', async ({ page }) => {
    const errors = watchErrors(page);
    await page.goto('/');

    await expect(page.locator('.ev-card', { hasText: 'E2E Fair' })).toBeVisible();
    await page.getByRole('link', { name: 'Sign In' }).click();
    await expect(page).toHaveURL(/\/login$/);
    expect(errors).toEqual([]);
});

test('a signed-out visitor is sent to sign in from a participant page, and an admin URL to the admin sign-in', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/login$/);

    await page.goto('/admin/events');
    await expect(page).toHaveURL(/\/admin\/login$/);
});
