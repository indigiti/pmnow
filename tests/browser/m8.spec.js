import { test, expect } from '@playwright/test';

test('M8 home channel interaction stays browser-safe', async ({ page }) => {
  const errors=[];
  page.on('pageerror',err=>errors.push(err.message));
  await page.goto('/');
  await expect(page.locator('.logo')).toContainText('PUNE');
  await expect(page.locator('#feed .story')).toHaveCount(8);

  await page.locator('[data-channel="traffic"]').click();
  const visible=page.locator('#feed .story:visible');
  await expect(visible.first()).toBeVisible();
  expect(await visible.count()).toBeGreaterThan(0);

  const save=page.locator('[data-bookmark]:visible').first();
  await save.click();
  await expect(save.locator('span')).toHaveText('Saved');

  const follow=page.locator('[data-follow]:visible').first();
  if (await follow.count()) {
    await follow.click();
    await expect(follow.locator('span')).toHaveText('Following');
  }
  expect(errors).toEqual([]);
});

test('feed cursor returns a non-overlapping next page', async ({ request }) => {
  const first=await request.get('/api/v1/feed?limit=2');
  expect(first.ok()).toBeTruthy();
  const a=await first.json();
  expect(a.data).toHaveLength(2);
  expect(a.meta.has_more).toBeTruthy();
  expect(a.meta.next_cursor).toBeTruthy();

  const second=await request.get('/api/v1/feed?limit=2&cursor='+encodeURIComponent(a.meta.next_cursor));
  expect(second.ok()).toBeTruthy();
  const b=await second.json();
  const idsA=new Set(a.data.map(x=>x.id));
  expect(b.data.every(x=>!idsA.has(x.id))).toBeTruthy();
});

test('analytics endpoint accepts a privacy-minimal event', async ({ page }) => {
  await page.goto('/');
  const token=await page.locator('meta[name="csrf-token"]').getAttribute('content');
  const response=await page.request.post('/api/v1/analytics/events',{
    headers:{'X-CSRF-Token':token,'Content-Type':'application/json','Origin':'http://127.0.0.1:8080'},
    data:{event:'page_view',properties:{path:'/browser-smoke'}}
  });
  expect(response.status()).toBe(202);
});
