import { test, expect } from '@playwright/test';

test.describe('Account Flow (Login + My Account UI)', () => {
  const baseURL = 'http://noyonaqa.local';

  test('User can login and view account details', async ({ page }) => {

    // =========================
    // 🔐 LOGIN
    // =========================
    await page.goto(`${baseURL}/login/`);

    await page.waitForSelector('#username');

    await page.fill('#username', 'admin');
    await page.fill('#password', 'admin');

    await page.click('button[name="login"]');

    await page.waitForURL('**/my-account/**');

    await expect(page).toHaveURL(/my-account/);
    await expect(page.locator('.noyona-account-profile-card__body')).toBeVisible();

    // =========================
    // 🧪 ACCOUNT UI TEST
    // =========================

    // ✅ Full Name
    const fullName = page.locator('label:has-text("Full Name:") + input');
    await expect(fullName).toBeVisible();
    await expect(fullName).toHaveValue('admin');
    await expect(fullName).toHaveAttribute('readonly', '');

    // ✅ Email
    const email = page.locator('label:has-text("Email:") + input');
    await expect(email).toBeVisible();
    await expect(email).toHaveValue('bruce.andrada@viteseo.ph');

    // ✅ Phone
    const phone = page.locator('label:has-text("Phone #:") + input');
    await expect(phone).toBeVisible();

    // ✅ Password
    const password = page.locator('label:has-text("Password:") + input');
    await expect(password).toBeVisible();
    await expect(password).toHaveAttribute('readonly', '');

    // =========================
    // 🔘 BUTTONS
    // =========================

    const changePasswordBtn = page.getByRole('link', { name: 'Change Password' });
    const editProfileBtn = page.getByRole('link', { name: 'Edit Profile Details' });

    await expect(changePasswordBtn).toBeVisible();
    await expect(editProfileBtn).toBeVisible();

    console.log('✅ Account UI verified successfully');

  });
});