import { test, expect } from '@playwright/test';

test('all navigation links work', async ({ page }) => {
  const baseURL = 'http://noyonaqa.local';

  await page.setViewportSize({ width: 1280, height: 800 });
  await page.goto(baseURL);
  await page.waitForSelector('#primary-nav');

  const links = page.locator('#primary-nav a');
  const count = await links.count();

  const tested = new Set();

  for (let i = 0; i < count; i++) {
    const href = await links.nth(i).getAttribute('href');

    if (!href || href === '#') continue;
    if (tested.has(href)) continue;

    if (href.includes('logout') || href.includes('wp-login')) continue;

    tested.add(href);

    const url = href.startsWith('http') ? href : `${baseURL}${href}`;

    console.log(`Testing link: ${href}`);

    const response = await page.goto(url);
    expect(response.status()).toBeLessThan(400);

    if (href.includes('my-account')) {
      await expect(page).toHaveURL(/login/);
    } else {
      await expect(page).toHaveURL(url);
    }

    console.log(`✔ Passed: ${href}`);

    await page.goto(baseURL);
  }
});