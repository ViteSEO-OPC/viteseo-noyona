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

    // skip unwanted
    if (
      href.includes('logout') ||
      href.includes('wp-login')
    ) continue;

    tested.add(href);

    const url = href.startsWith('http') ? href : `${baseURL}${href}`;

    console.log(`Testing link: ${href}`);

    await page.goto(url);

    if (href.includes('my-account')) {
      await expect(page).toHaveURL(/login/);
    } else {
      await expect(page).toHaveURL(new RegExp(url.replace(/\//g, '\\/')));
    }

    await page.goto(baseURL);
  }
});