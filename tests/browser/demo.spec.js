import { test, expect } from '@playwright/test';

test('@demo web admin can load and selectively clean synthetic QA records', async ({ page }) => {
  await page.goto('/admin/demo');
  await expect(page.getByRole('heading', { name: 'Demo Data' })).toBeVisible();
  await expect(page.locator('[data-demo-count="stories"]')).toHaveText('0');

  await page.locator('[data-demo-apply]').click();
  await expect(page.locator('[data-demo-status]')).toContainText('Demo data ready.');
  await expect(page.locator('[data-demo-count="stories"]')).toHaveText('18');
  await expect(page.locator('[data-demo-count="contents"]')).toHaveText('7');
  await expect(page.locator('[data-demo-count="utility-updates"]')).toHaveText('9');

  await page.locator('[data-demo-apply]').click();
  await expect(page.locator('[data-demo-status]')).toContainText('0 new synthetic record(s)');

  const purge=page.locator('[data-demo-purge]');
  await expect(purge).toBeDisabled();
  await page.locator('[data-demo-purge-confirm]').fill('REMOVE DEMO');
  await expect(purge).toBeEnabled();
  await purge.click();
  await expect(page.locator('[data-demo-status]')).toContainText('Demo cleanup complete.');
  await expect(page.locator('[data-demo-count="stories"]')).toHaveText('0');
  await expect(page.locator('[data-demo-count="contents"]')).toHaveText('0');
  await expect(page.locator('[data-demo-count="utility-updates"]')).toHaveText('0');

  const originalFeed=await page.request.get('/api/v1/feed?limit=1');
  expect(originalFeed.ok()).toBeTruthy();
  const payload=await originalFeed.json();
  expect(payload.data.length).toBeGreaterThan(0);
});
