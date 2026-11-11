const { test, expect } = require('@playwright/test');
const { signInAs, watchErrors } = require('./helpers');

/**
 * The participant's menu, as the office has it: a navy rail fixed down the left
 * on a desk, which the menu button hides and shows; a drawer on a phone. A
 * browser behaviour, so only a browser can check it.
 */
const DESK = { width: 1280, height: 800 };
const PHONE = { width: 390, height: 844 };

test('on a desk the menu is a rail that stays, and the button hides and shows it', async ({ page }) => {
    await page.setViewportSize(DESK);
    const errors = watchErrors(page);
    await signInAs(page, 'tenun@e2e.test');

    const menu = page.getByRole('button', { name: 'Open menu' });
    const rail = page.locator('#apSidebar');
    const content = page.locator('#main-content');

    // There without asking, beside the page, not over it.
    await expect(rail.getByRole('link', { name: 'Dashboard' })).toBeVisible();
    await expect(rail.getByRole('link', { name: 'Dashboard' })).toHaveAttribute('aria-current', 'page');
    await expect(rail.getByRole('link', { name: 'Notifications' })).toBeVisible();
    await expect(rail.getByRole('button', { name: 'Sign Out' })).toBeVisible();
    await expect(rail.getByRole('button', { name: 'Close menu' })).toBeHidden();
    await expect(menu).toHaveAttribute('aria-expanded', 'true');
    const railBox = await rail.boundingBox();
    expect((await content.boundingBox()).x).toBeGreaterThanOrEqual(railBox.x + railBox.width);

    // The button hides it and the page takes the room; again shows it.
    await menu.click();
    await expect(rail.getByRole('link', { name: 'Notifications' })).toBeHidden();
    await expect(menu).toHaveAttribute('aria-expanded', 'false');
    await expect.poll(async () => (await content.boundingBox()).x).toBeLessThan(railBox.width);
    await menu.click();
    await expect(rail.getByRole('link', { name: 'Notifications' })).toBeVisible();

    // The same rail on the public pages, so the frame holds from page to page.
    await rail.getByRole('link', { name: 'Events', exact: true }).click();
    await expect(page).toHaveURL(/\/$/);
    await expect(rail.getByRole('link', { name: 'Events', exact: true })).toHaveAttribute('aria-current', 'page');

    await Promise.all([page.waitForNavigation(), rail.getByRole('link', { name: 'Notifications' }).click()]);
    await expect(page).toHaveURL(/\/notifications$/);

    await Promise.all([page.waitForNavigation(), rail.getByRole('link', { name: 'Past events' }).click()]);
    await expect(page).toHaveURL(/\/past-events$/);
    await expect(page.getByRole('heading', { name: 'Your company on record' })).toBeVisible();

    await Promise.all([page.waitForNavigation(), rail.getByRole('link', { name: 'Profile' }).click()]);
    await expect(page).toHaveURL(/\/profile$/);
    await expect(rail.getByRole('link', { name: 'Profile' })).toHaveAttribute('aria-current', 'page');
    await expect(page.getByRole('heading', { name: 'Change password' })).toBeVisible();
    expect(errors).toEqual([]);
});

test('on a phone the menu is a drawer: opened by the button, closed by Escape, a tap outside or its own button', async ({ page }) => {
    await page.setViewportSize(PHONE);
    const errors = watchErrors(page);
    await signInAs(page, 'tenun@e2e.test');

    const menu = page.getByRole('button', { name: 'Open menu' });
    const drawer = page.locator('#apSidebar');

    // Closed: not shown, and its links cannot be reached.
    await expect(menu).toHaveAttribute('aria-expanded', 'false');
    await expect(drawer.getByRole('link', { name: 'Notifications' })).toBeHidden();

    await menu.click();
    await expect(menu).toHaveAttribute('aria-expanded', 'true');
    await expect(drawer.getByRole('link', { name: 'Notifications' })).toBeVisible();
    await expect(drawer.getByRole('button', { name: 'Sign Out' })).toBeVisible();
    await expect(drawer.getByRole('link', { name: 'Dashboard' })).toBeFocused();

    // Escape closes it and focus goes back to the menu button.
    await page.keyboard.press('Escape');
    await expect(drawer.getByRole('link', { name: 'Notifications' })).toBeHidden();
    await expect(menu).toBeFocused();

    await menu.click();
    await page.locator('#apOverlay').click({ position: { x: PHONE.width - 40, y: 400 } });
    await expect(drawer.getByRole('link', { name: 'Notifications' })).toBeHidden();
    await menu.click();
    await drawer.getByRole('button', { name: 'Close menu' }).click();
    await expect(drawer.getByRole('link', { name: 'Notifications' })).toBeHidden();
    expect(errors).toEqual([]);
});

test('signed out on a desk there is no rail: the nav sits in the bar', async ({ page }) => {
    await page.setViewportSize(DESK);
    await page.goto('/');

    await expect(page.getByRole('button', { name: 'Open menu' })).toBeHidden();
    await expect(page.locator('#apSidebar').getByRole('link', { name: 'Events', exact: true })).toBeHidden();
    await expect(page.locator('.ap-topnav').getByRole('link', { name: 'Events' })).toBeVisible();
    expect((await page.locator('#main-content').boundingBox()).x).toBe(0);
});
