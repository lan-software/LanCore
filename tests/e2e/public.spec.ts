import { expect, test } from '@playwright/test';

test.describe('Public pages', () => {
    test('homepage loads', async ({ page }) => {
        await page.goto('/');
        await expect(page).toHaveTitle(/.+/);
    });

    test('login page is accessible', async ({ page }) => {
        await page.goto('/login');
        await expect(page.locator('input[name="email"]')).toBeVisible();
        await expect(page.locator('input[name="password"]')).toBeVisible();
    });

    test('register page is accessible', async ({ page }) => {
        await page.goto('/register');
        await expect(page.locator('input[name="email"]')).toBeVisible();
    });
});

test.describe('Authentication', () => {
    test('shows validation errors on empty login', async ({ page }) => {
        await page.goto('/login');
        const emailInput = page.locator('input[name="email"]');

        await page.getByRole('button', { name: /log in/i }).click();

        // Browser enforces HTML5 required validation before form submission
        await expect(emailInput).toHaveJSProperty('validity.valueMissing', true);
    });
});
