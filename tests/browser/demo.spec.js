import { test, expect } from '@playwright/test';

test('@demo web admin can load and selectively clean synthetic QA records', async ({ page }) => {
  await page.goto('/admin/demo');
  await expect(page.getByRole('heading', { name: 'Demo Data', exact: true })).toBeVisible();
  await expect(page.locator('[data-demo-count="stories"]')).toHaveText('0');

  await page.locator('[data-demo-apply]').click();
  await expect(page.locator('[data-demo-status]')).toContainText('Demo data ready.');
  await expect(page.locator('[data-demo-count="stories"]')).toHaveText('18');
  await expect(page.locator('[data-demo-count="contents"]')).toHaveText('7');
  await expect(page.locator('[data-demo-count="utility-updates"]')).toHaveText('9');

  // Existing synthetic records are visually marked using the supplied seal,
  // not with a raw [DEMO] headline. The disclosure must remain visible.
  await page.goto('/');
  const sampleCard=page.locator('#feed .story').filter({has:page.locator('.pm-sample-mark')}).first();
  await expect(sampleCard).toBeVisible();
  await expect(sampleCard.locator('h2')).not.toContainText('[DEMO]');
  await expect(sampleCard.locator('.pm-sample-mark')).toHaveAttribute('aria-label','Synthetic test content — not verified reporting');
  await expect(sampleCard.locator('.pm-sample-mark')).toHaveText('');
  await expect(sampleCard.locator('.sourcepill')).not.toContainText(/sample|manual/i);
  const cardBadgeSize=await sampleCard.locator('.pm-sample-mark img').evaluate(img=>({width:img.getBoundingClientRect().width,height:img.getBoundingClientRect().height}));
  expect(cardBadgeSize.width).toBeLessThanOrEqual(17);
  expect(cardBadgeSize.height).toBeLessThanOrEqual(17);
  await expect(sampleCard.locator('.pm-sample-mark img')).toHaveAttribute('src','/media/sample-content-badge.svg');
  const sampleId=await sampleCard.getAttribute('data-story-id');
  expect(sampleId).toBeTruthy();
  const [sitemap,newsSitemap,channel]=await Promise.all([
    page.request.get('/sitemap.xml'),
    page.request.get('/news-sitemap.xml'),
    page.request.get('/channels/whatsapp.json')
  ]);
  expect(sitemap.ok()).toBeTruthy();
  expect(newsSitemap.ok()).toBeTruthy();
  expect(channel.ok()).toBeTruthy();
  expect(await sitemap.text()).not.toContain(sampleId);
  expect(await newsSitemap.text()).not.toContain(sampleId);
  const channelPayload=await channel.json();
  expect(JSON.stringify(channelPayload.items)).not.toContain(sampleId);
  await sampleCard.locator('.tapread').click();
  await expect(page.locator('.pm-sample-mark').first()).toBeVisible();
  await expect(page.locator('.pm-sample-mark').first()).toHaveText('');

  await page.goto('/watch');
  const watchBadge=page.locator('.reel .pm-sample-mark').first();
  await expect(watchBadge).toBeVisible();
  await expect(watchBadge).toHaveText('');
  const watchSizes=await watchBadge.evaluate(el=>{
    const icon=el.querySelector('img');
    const reel=el.closest('.reel');
    const hero=reel?.querySelector(':scope > img');
    return {
      markW:el.getBoundingClientRect().width,
      markH:el.getBoundingClientRect().height,
      iconW:icon?.getBoundingClientRect().width,
      iconH:icon?.getBoundingClientRect().height,
      heroW:hero?.getBoundingClientRect().width,
      heroH:hero?.getBoundingClientRect().height
    };
  });
  expect(watchSizes.markW).toBeLessThanOrEqual(18);
  expect(watchSizes.markH).toBeLessThanOrEqual(18);
  expect(watchSizes.iconW).toBeLessThanOrEqual(18);
  expect(watchSizes.iconH).toBeLessThanOrEqual(18);
  expect(watchSizes.heroW).toBeGreaterThan(250);
  expect(watchSizes.heroH).toBeGreaterThan(350);
  await page.goto('/admin/demo');

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
