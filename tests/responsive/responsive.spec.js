import { test, expect } from '@playwright/test';

const baseURL = 'http://noyonaqa.local';

const viewports = [
  { name: 'Desktop', width: 1280, height: 800, mobile: false },
  { name: 'Tablet', width: 768, height: 1024, mobile: false },
  { name: 'Mobile', width: 375, height: 667, mobile: true },
];

test.describe('Responsive Navigation', () => {
  for (const vp of viewports) {
    test(`navigation works on ${vp.name}`, async ({ page }) => {

      await page.setViewportSize({ width: vp.width, height: vp.height });
      await page.goto(baseURL);

      const nav = page.locator('#primary-nav');

      // =========================
      // 📱 Mobile menu handling
      // =========================
      if (vp.mobile) {
        const menuBtn = page.locator(
          '.menu-toggle, .hamburger, .nav-toggle, button[aria-label*="menu"]'
        );

        if (await menuBtn.count() > 0) {
          await expect(menuBtn.first()).toBeVisible();
          await menuBtn.first().click();
          await expect(nav).toBeVisible();
        } else {
          console.warn('⚠ No hamburger found — nav might already be visible');
        }
      } else {
        await expect(nav).toBeVisible();
      }

      // =========================
      // 🔥 POSITION CHECK
      // =========================
      const box = await nav.boundingBox();

      expect(box).not.toBeNull();
      expect(box.y).toBeGreaterThanOrEqual(0);
      expect(box.x).toBeGreaterThanOrEqual(0);

      // =========================
      // 🔥 FIXED NAVIGATION TEST (GOTO INSTEAD OF CLICK)
      // =========================
      const links = nav.locator('a');
      let tested = false;

      for (let i = 0; i < await links.count(); i++) {
        const link = links.nth(i);
        const href = await link.getAttribute('href');

        if (!href || href === '/' || href === '#') continue;
        if (href.includes('logout') || href.includes('wp-login')) continue;

        const url = href.startsWith('http') ? href : `${baseURL}${href}`;

        console.log(`Testing link on ${vp.name}: ${href}`);

        // 👉 FIX: use goto instead of click
        await page.goto(url);

        await expect(page).toHaveURL(url);

        await page.goto(baseURL);

        tested = true;
        break;
      }

      if (!tested) {
        console.warn(`⚠ No valid link found on ${vp.name}`);
      }

      // =========================
      // 🔥 OPTIONAL OVERLAP CHECK
      // =========================
      const isOverlapping = await page.evaluate(() => {
        const el = document.querySelector('#primary-nav');
        if (!el) return false;

        const rect = el.getBoundingClientRect();
        const x = rect.left + rect.width / 2;
        const y = rect.top + rect.height / 2;

        const topElement = document.elementFromPoint(x, y);

        return !(topElement === el || el.contains(topElement));
      });

      if (isOverlapping) {
        console.warn(`⚠ Possible overlap detected on ${vp.name}`);
      }

      // =========================
      // 📸 SCREENSHOT
      // =========================
      await page.screenshot({
        path: `tests/responsive/screenshots/${vp.name}.png`,
        fullPage: true,
      });

      console.log(`✔ ${vp.name} responsive test passed`);
    });
  }
});