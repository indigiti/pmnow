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


test('M9 discovery endpoints and NewsArticle metadata are crawlable', async ({ page, request }) => {
  const sitemap=await request.get('/sitemap.xml');
  expect(sitemap.ok()).toBeTruthy();
  expect(await sitemap.text()).toContain('/pune/');

  const news=await request.get('/news-sitemap.xml');
  expect(news.ok()).toBeTruthy();
  expect(await news.text()).toContain('<news:news>');

  const rss=await request.get('/rss.xml');
  expect(rss.ok()).toBeTruthy();
  expect(await rss.text()).toContain('<rss version="2.0">');

  const feed=await request.get('/api/v1/feed?limit=1');
  const payload=await feed.json();
  const path=payload.data[0].path;
  expect(path).toContain('/pune/');

  await page.goto(path);
  const canonical=await page.locator('link[rel="canonical"]').getAttribute('href');
  expect(canonical).toContain(path);
  const jsonLd=await page.locator('script[type="application/ld+json"]').textContent();
  expect(jsonLd).toContain('"@type":"NewsArticle"');

  const category=await request.get('/category/traffic');
  expect(category.ok()).toBeTruthy();
});

test('M10 My Pune preferences persist and shape reader state', async ({ page }) => {
  const errors=[];
  page.on('pageerror',err=>errors.push(err.message));
  await page.goto('/profile');
  const aundh=page.locator('input[name="areas"][value="Aundh"]');
  await page.locator('label.pref-option',{has:aundh}).click();
  await expect(aundh).toBeChecked();
  const lifestyle=page.locator('input[name="channels"][value="Lifestyle"]');
  await page.locator('label.pref-option',{has:lifestyle}).click();
  await expect(lifestyle).toBeChecked();
  await page.locator('[data-preferences] button[type="submit"]').click();
  await expect(page.locator('[data-preference-status]')).toHaveText('Saved');

  const me=await page.request.get('/api/v1/me');
  expect(me.ok()).toBeTruthy();
  const payload=await me.json();
  expect(payload.data.user.preferences.areas).toContain('Aundh');
  expect(payload.data.user.preferences.channels).toContain('Lifestyle');

  await page.goto('/');
  await expect(page.locator('.personalization-strip')).toContainText('Aundh');
  expect(errors).toEqual([]);
});


test('M10 Near You surface reflects selected neighbourhoods', async ({ page }) => {
  await page.goto('/profile');
  const baner=page.locator('input[name="areas"][value="Baner"]');
  if(!(await baner.isChecked())) await page.locator('label.pref-option',{has:baner}).click();
  await page.locator('[data-preferences] button[type="submit"]').click();
  await expect(page.locator('[data-preference-status]')).toHaveText('Saved');

  await page.goto('/near-you');
  await expect(page.locator('h1')).toHaveText('Near You');
  await expect(page.locator('.near-you-areas')).toContainText('Baner');
  await expect(page.locator('#nearYouFeed .story').first()).toBeVisible();
  await expect(page.locator('#nearYouFeed .story').first()).toContainText('Pune food street');
});


test('M11 alert preferences and channel feed are browser-safe', async ({ page, request }) => {
  const errors=[];page.on('pageerror',err=>errors.push(err.message));
  await page.goto('/profile');
  const form=page.locator('[data-notification-preferences]');
  await expect(form).toBeVisible();
  const morning=form.locator('input[name="morning_digest"]');
  if(!(await morning.isChecked())) await morning.check();
  await form.locator('button[type="submit"]').click();
  await expect(page.locator('[data-notification-status]')).toHaveText('Saved');

  const me=await page.request.get('/api/v1/me');const state=await me.json();
  expect(state.data.user.notification_preferences.morning_digest).toBeTruthy();

  const channel=await request.get('/channels/whatsapp.json');
  expect(channel.ok()).toBeTruthy();
  const payload=await channel.json();
  expect(payload.channel).toBe('whatsapp');
  expect(payload.items.length).toBeGreaterThan(0);
  expect(payload.items[0].url).toContain('/pune/');
  expect(errors).toEqual([]);
});


test('M12 utility board is source-labelled and followable', async ({ page, request }) => {
  const errors=[];page.on('pageerror',err=>errors.push(err.message));
  await page.goto('/utility');
  await expect(page.locator('.utility-hero h1')).toHaveText('City status');
  await expect(page.locator('.utility-card').first()).toBeVisible();
  await expect(page.locator('.utility-card').first()).toContainText('PMNow demo seed');
  await expect(page.locator('.utility-card').first()).toContainText('Demo:');

  const button=page.locator('[data-utility-follow]').first();
  const entityId=await button.getAttribute('data-utility-follow');
  await button.click();
  await expect(button.locator('span')).toHaveText('Following');

  const entities=await request.get('/api/v1/utility/entities');
  expect(entities.ok()).toBeTruthy();
  const entitiesPayload=await entities.json();
  expect(entitiesPayload.data.length).toBeGreaterThan(0);

  const filtered=await request.get('/api/v1/utility?kind=traffic&area=Shivajinagar');
  expect(filtered.ok()).toBeTruthy();
  const payload=await filtered.json();
  expect(payload.data.some(row=>row.entity?.id===entityId || row.entity?.kind==='traffic')).toBeTruthy();
  expect(errors).toEqual([]);
});

test('M12 utility entity page exposes verification history', async ({ page }) => {
  await page.goto('/utility/university-road-traffic');
  await expect(page.locator('.utility-entity-hero h1')).toContainText('University Road');
  await expect(page.locator('.utility-card').first()).toContainText('Verified');
  await expect(page.locator('.utility-card').first()).toContainText('Demo:');
});


test('M12 duplicate utility follow controls stay synchronized', async ({ page }) => {
  await page.goto('/utility');
  const first=page.locator('[data-utility-follow]').first();
  const id=await first.getAttribute('data-utility-follow');
  const controls=page.locator('[data-utility-follow="'+id+'"]');
  if(await controls.count()>1){
    await first.click();
    const expected=await first.locator('span').textContent();
    for(let i=0;i<await controls.count();i++) await expect(controls.nth(i).locator('span')).toHaveText(expected);
  }
});

test('M13 Community accepts moderation-first submissions', async ({ page, request }) => {
  const errors=[];page.on('pageerror',err=>errors.push(err.message));
  await page.goto('/community');
  await expect(page.locator('h1')).toHaveText('Pune Community');
  await page.locator('[data-community-form] [name=area]').fill('Baner');
  await page.locator('[data-community-form] [name=title]').fill('Browser community smoke');
  await page.locator('[data-community-form] [name=body]').fill('Moderation-first browser submission');
  await page.locator('[data-community-form] button[type=submit]').click();
  await expect(page.locator('[data-community-status]')).toHaveText('Submitted for moderation');
  const feed=await request.get('/api/v1/community?area=Baner');expect(feed.ok()).toBeTruthy();
  const payload=await feed.json();expect(payload.data.some(x=>x.title==='Browser community smoke')).toBeFalsy();
  expect(errors).toEqual([]);
});

test('M14 Utility board exposes freshness state', async ({ page }) => {
  await page.goto('/utility');
  await expect(page.locator('.utility-card').first()).toContainText('Freshness:');
});

test('M15 Events public surface and API are browser-safe', async ({ page, request }) => {
  const errors=[];page.on('pageerror',err=>errors.push(err.message));
  await page.goto('/events');
  await expect(page.locator('h1')).toHaveText("What's on in Pune");
  const response=await request.get('/api/v1/events');expect(response.ok()).toBeTruthy();
  const payload=await response.json();expect(Array.isArray(payload.data)).toBeTruthy();
  expect(errors).toEqual([]);
});
